<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\BrandKitCategory;
use App\Modules\TaskManagement\Enums\CompanyLogoVariant;
use App\Modules\TaskManagement\Http\Requests\BrandKitAssetUploadRequest;
use App\Modules\TaskManagement\Http\Requests\BrandKitCompanyRequest;
use App\Modules\TaskManagement\Http\Requests\CompanyLogoUploadRequest;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyPhoneNumber;
use App\Modules\TaskManagement\Services\CompanyShareLinkService;
use App\Modules\TaskManagement\Services\MediaStorageService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BrandKitController extends Controller
{
    public function __construct(
        protected CompanyShareLinkService $shareLinks,
        protected MediaStorageService $mediaStorage,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewLogoLibrary', Company::class);

        $search = trim((string) $request->string('search'));

        $companies = Company::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('website', 'like', "%{$search}%")
                        ->orWhere('primary_contact_email', 'like', "%{$search}%")
                        ->orWhere('primary_contact_phone', 'like', "%{$search}%")
                        ->orWhereHas('phoneNumbers', fn ($phones) => $phones->where('phone', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(Pagination::perPage($request, 12))
            ->withQueryString()
            ->through(fn (Company $company) => $this->summarise($company, $request));

        return Inertia::render('TaskManagement/brand-kit/index', [
            'companies' => $companies,
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
            'can' => [
                'manage' => $request->user()->can(Ability::ManageCompanyLogos->value)
                    || $request->user()->can(Ability::ManageCompanies->value),
            ],
        ]);
    }

    public function show(Request $request, Company $company): Response
    {
        $this->authorize('viewLogo', $company);

        $company->load(['shareLink', 'phoneNumbers']);

        return Inertia::render('TaskManagement/brand-kit/show', [
            'company' => $this->detail($company, $request),
            'categories' => BrandKitCategory::options(),
            'variants' => CompanyLogoVariant::options(),
            'can' => [
                'manage' => $request->user()->can('manageLogos', $company),
                'share' => $request->user()->can('shareLogos', $company),
            ],
        ]);
    }

    public function update(BrandKitCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('manageLogos', $company);

        $validated = $request->validated();
        $phones = $validated['phones'] ?? [];
        unset($validated['phones']);

        DB::transaction(function () use ($company, $validated, $phones): void {
            $company->update($validated);
            $this->syncPhoneNumbers($company, $phones);
        });

        return back()->with('success', 'Company details updated.');
    }

    public function storeLogo(CompanyLogoUploadRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('manageLogos', $company);

        $variant = CompanyLogoVariant::from($request->validated('variant'));
        $file = $request->file('file');

        $company->getMedia('logos')
            ->filter(fn (Media $media) => ($media->getCustomProperty('variant') ?? null) === $variant->value)
            ->each(fn (Media $media) => $this->mediaStorage->deleteMedia($media, 'manual_logo_replace', allowPermanent: true));

        $company->addMedia($file)
            ->withCustomProperties([
                'variant' => $variant->value,
                'uploaded_by_user_id' => $request->user()->id,
            ])
            ->toMediaCollection('logos');

        return back()->with('success', 'Logo uploaded.');
    }

    public function storeAsset(BrandKitAssetUploadRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('manageLogos', $company);

        $category = BrandKitCategory::from($request->validated('category'));
        $title = trim((string) $request->validated('title'));
        $description = trim((string) ($request->validated('description') ?? ''));
        $files = $request->file('files', []);

        if ($category === BrandKitCategory::Logos) {
            $variant = CompanyLogoVariant::from($request->validated('variant'));
            $file = $files[0];

            $company->getMedia('logos')
                ->filter(fn (Media $media) => ($media->getCustomProperty('variant') ?? null) === $variant->value)
                ->each(fn (Media $media) => $this->mediaStorage->deleteMedia($media, 'manual_logo_replace', allowPermanent: true));

            $company->addMedia($file)
                ->withCustomProperties([
                    'variant' => $variant->value,
                    'title' => $title,
                    'description' => $description !== '' ? $description : null,
                    'uploaded_by_user_id' => $request->user()->id,
                ])
                ->toMediaCollection('logos');

            return back()->with('success', 'Brand asset uploaded.');
        }

        $assetId = (string) Str::uuid();

        foreach ($files as $file) {
            $company->addMedia($file)
                ->withCustomProperties([
                    'asset_id' => $assetId,
                    'category' => $category->value,
                    'title' => $title,
                    'description' => $description !== '' ? $description : null,
                    'uploaded_by_user_id' => $request->user()->id,
                ])
                ->toMediaCollection('brand_assets');
        }

        return back()->with('success', 'Brand asset uploaded.');
    }

    public function destroyLogo(Request $request, Company $company, Media $media): RedirectResponse
    {
        $this->assertLogoMedia($company, $media);
        $this->authorize('manageLogos', $company);

        $this->mediaStorage->deleteMedia($media, 'manual_logo_delete', allowPermanent: true);

        return redirect()
            ->route('tasks.brand-kit.show', $company)
            ->with('success', 'Logo removed.');
    }

    public function destroyAsset(Request $request, Company $company, string $asset): RedirectResponse
    {
        $this->authorize('manageLogos', $company);

        $mediaItems = $company->getMedia('brand_assets')
            ->filter(fn (Media $media) => (string) $media->getCustomProperty('asset_id') === $asset);

        abort_if($mediaItems->isEmpty(), 404);

        $mediaItems->each(fn (Media $media) => $this->mediaStorage->deleteMedia($media, 'manual_brand_asset_delete', allowPermanent: true));

        return back()->with('success', 'Brand asset removed.');
    }

    public function previewLogo(Request $request, Company $company, Media $media): SymfonyResponse
    {
        $this->assertLogoMedia($company, $media);
        $this->authorize('viewLogo', $company);
        $this->assertMediaFileExists($media);

        return $media->toInlineResponse($request);
    }

    public function downloadLogo(Request $request, Company $company, Media $media): SymfonyResponse
    {
        $this->assertLogoMedia($company, $media);
        $this->authorize('viewLogo', $company);
        $this->assertMediaFileExists($media);

        return $media->toResponse($request);
    }

    public function previewAsset(Request $request, Company $company, Media $media): SymfonyResponse
    {
        $this->assertBrandAssetMedia($company, $media);
        $this->authorize('viewLogo', $company);
        $this->assertMediaFileExists($media);

        return $media->toInlineResponse($request);
    }

    public function downloadAsset(Request $request, Company $company, Media $media): SymfonyResponse
    {
        $this->assertBrandAssetMedia($company, $media);
        $this->authorize('viewLogo', $company);
        $this->assertMediaFileExists($media);

        return $media->toResponse($request);
    }

    public function shareLink(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('shareLogos', $company);

        $link = $this->shareLinks->getOrCreate($company, $request->user());

        return back()->with('success', 'Share link ready.')->with('share_url', $link->publicUrl());
    }

    /**
     * @param  list<array{id?: int|null, label?: string|null, phone: string, is_primary?: bool}>  $phones
     */
    protected function syncPhoneNumbers(Company $company, array $phones): void
    {
        $phones = collect($phones)
            ->filter(fn (array $row) => trim($row['phone'] ?? '') !== '')
            ->values();

        if ($phones->isEmpty()) {
            $company->phoneNumbers()->delete();
            $company->update(['primary_contact_phone' => null]);

            return;
        }

        if (! $phones->contains(fn (array $row) => (bool) ($row['is_primary'] ?? false))) {
            $phones = $phones->map(function (array $row, int $index) {
                $row['is_primary'] = $index === 0;

                return $row;
            });
        } else {
            $primarySet = false;
            $phones = $phones->map(function (array $row) use (&$primarySet) {
                if ((bool) ($row['is_primary'] ?? false) && ! $primarySet) {
                    $primarySet = true;

                    return $row;
                }

                $row['is_primary'] = false;

                return $row;
            });
        }

        $keptIds = [];

        foreach ($phones as $index => $row) {
            $payload = [
                'label' => isset($row['label']) ? trim((string) $row['label']) : null,
                'phone' => trim((string) $row['phone']),
                'is_primary' => (bool) ($row['is_primary'] ?? false),
                'sort_order' => $index,
            ];

            if (! empty($row['id'])) {
                /** @var CompanyPhoneNumber|null $existing */
                $existing = $company->phoneNumbers()->whereKey($row['id'])->first();
                if ($existing !== null) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $company->phoneNumbers()->create($payload);
            $keptIds[] = $created->id;
        }

        $company->phoneNumbers()->whereNotIn('id', $keptIds)->delete();

        $primaryPhone = $company->phoneNumbers()->where('is_primary', true)->value('phone')
            ?? $company->phoneNumbers()->orderBy('sort_order')->value('phone');

        $company->update(['primary_contact_phone' => $primaryPhone]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(Company $company, Request $request): array
    {
        $company->loadMissing(['media', 'phoneNumbers']);

        return [
            'id' => $company->id,
            'name' => $company->name,
            'website' => $company->website,
            'email' => $company->primary_contact_email,
            'phone' => $company->primary_contact_phone,
            'phones' => $this->phonePayload($company),
            'asset_count' => $this->assetCount($company),
            'preview_assets' => $this->previewAssets($company, $request),
            'can' => [
                'manage' => $request->user()->can('manageLogos', $company),
                'share' => $request->user()->can('shareLogos', $company),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function detail(Company $company, Request $request): array
    {
        $company->loadMissing(['media', 'phoneNumbers']);

        return [
            'id' => $company->id,
            'name' => $company->name,
            'website' => $company->website,
            'email' => $company->primary_contact_email,
            'phone' => $company->primary_contact_phone,
            'phones' => $this->phonePayload($company),
            'asset_count' => $this->assetCount($company),
            'logos' => $this->logoPayload($company, $request),
            'assets' => $this->brandAssetPayload($company, $request),
            'share_url' => $company->shareLink?->publicUrl(),
        ];
    }

    protected function assetCount(Company $company): int
    {
        return $company->getMedia('logos')->count() + $company->getMedia('brand_assets')->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function phonePayload(Company $company): array
    {
        if ($company->relationLoaded('phoneNumbers') && $company->phoneNumbers->isNotEmpty()) {
            return $company->phoneNumbers
                ->map(fn (CompanyPhoneNumber $phone) => [
                    'id' => $phone->id,
                    'label' => $phone->label,
                    'phone' => $phone->phone,
                    'is_primary' => $phone->is_primary,
                ])
                ->values()
                ->all();
        }

        if ($company->primary_contact_phone) {
            return [[
                'id' => null,
                'label' => 'Primary',
                'phone' => $company->primary_contact_phone,
                'is_primary' => true,
            ]];
        }

        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function previewAssets(Company $company, Request $request): array
    {
        $previews = collect($this->logoPayload($company, $request))
            ->map(fn (array $logo) => [
                'uuid' => $logo['uuid'],
                'name' => $logo['variant_label'],
                'preview_url' => $logo['preview_url'],
                'download_url' => $logo['download_url'],
                'is_image' => str_starts_with((string) $logo['mime'], 'image/'),
            ]);

        if ($previews->count() >= 4) {
            return $previews->take(4)->values()->all();
        }

        $brandImages = $company->getMedia('brand_assets')
            ->filter(fn (Media $media) => str_starts_with((string) $media->mime_type, 'image/'))
            ->take(4 - $previews->count())
            ->map(fn (Media $media) => [
                'uuid' => $media->uuid,
                'name' => (string) ($media->getCustomProperty('title') ?? $media->file_name),
                'preview_url' => route('tasks.brand-kit.assets.preview', [
                    'company' => $company,
                    'media' => $media->uuid,
                ]),
                'download_url' => route('tasks.brand-kit.assets.download', [
                    'company' => $company,
                    'media' => $media->uuid,
                ]),
                'is_image' => true,
            ]);

        return $previews->concat($brandImages)->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function logoPayload(Company $company, Request $request): array
    {
        return $company->getMedia('logos')
            ->sortBy(fn (Media $media) => $media->getCustomProperty('variant'))
            ->map(function (Media $media) use ($company, $request): array {
                $variant = CompanyLogoVariant::tryFrom((string) $media->getCustomProperty('variant'));

                return [
                    'uuid' => $media->uuid,
                    'name' => $media->file_name,
                    'mime' => $media->mime_type,
                    'size' => $media->size,
                    'variant' => $variant?->value,
                    'variant_label' => $variant?->label() ?? 'Logo',
                    'title' => $media->getCustomProperty('title'),
                    'description' => $media->getCustomProperty('description'),
                    'preview_url' => route('tasks.brand-kit.logos.preview', [
                        'company' => $company,
                        'media' => $media->uuid,
                    ]),
                    'download_url' => route('tasks.brand-kit.logos.download', [
                        'company' => $company,
                        'media' => $media->uuid,
                    ]),
                    'can_delete' => $request->user()->can('manageLogos', $company),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function brandAssetPayload(Company $company, Request $request): array
    {
        return $this->groupBrandAssets($company->getMedia('brand_assets'), $company, $request);
    }

    /**
     * @param  Collection<int, Media>  $mediaItems
     * @return list<array<string, mixed>>
     */
    protected function groupBrandAssets(Collection $mediaItems, Company $company, Request $request): array
    {
        return $mediaItems
            ->groupBy(fn (Media $media) => (string) $media->getCustomProperty('asset_id'))
            ->map(function (Collection $files, string $assetId) use ($company, $request): array {
                /** @var Media $first */
                $first = $files->first();
                $category = BrandKitCategory::tryFrom((string) $first->getCustomProperty('category'));

                return [
                    'asset_id' => $assetId,
                    'category' => $category?->value,
                    'category_label' => $category?->label() ?? 'Brand asset',
                    'title' => (string) ($first->getCustomProperty('title') ?? $first->file_name),
                    'description' => $first->getCustomProperty('description'),
                    'files' => $files->map(fn (Media $media) => $this->filePayload($media, $company))->values()->all(),
                    'can_delete' => $request->user()->can('manageLogos', $company),
                ];
            })
            ->sortBy('title')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function filePayload(Media $media, Company $company): array
    {
        $extension = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));

        return [
            'uuid' => $media->uuid,
            'name' => $media->file_name,
            'mime' => $media->mime_type,
            'size' => $media->size,
            'extension' => $extension,
            'file_type' => str_starts_with((string) $media->mime_type, 'image/') ? 'image' : 'file',
            'preview_url' => route('tasks.brand-kit.assets.preview', [
                'company' => $company,
                'media' => $media->uuid,
            ]),
            'download_url' => route('tasks.brand-kit.assets.download', [
                'company' => $company,
                'media' => $media->uuid,
            ]),
        ];
    }

    protected function assertLogoMedia(Company $company, Media $media): void
    {
        abort_unless(
            $media->collection_name === 'logos'
                && $media->model_type === $company->getMorphClass()
                && (int) $media->model_id === (int) $company->id,
            404,
        );
    }

    protected function assertBrandAssetMedia(Company $company, Media $media): void
    {
        abort_unless(
            $media->collection_name === 'brand_assets'
                && $media->model_type === $company->getMorphClass()
                && (int) $media->model_id === (int) $company->id,
            404,
        );
    }

    protected function assertMediaFileExists(Media $media): void
    {
        if (! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())) {
            abort(404);
        }
    }
}
