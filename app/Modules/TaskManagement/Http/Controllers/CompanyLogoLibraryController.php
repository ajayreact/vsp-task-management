<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Modules\Core\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Enums\CompanyLogoVariant;
use App\Modules\TaskManagement\Http\Requests\CompanyLogoLibraryRequest;
use App\Modules\TaskManagement\Http\Requests\CompanyLogoUploadRequest;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Services\CompanyShareLinkService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CompanyLogoLibraryController extends Controller
{
    public function __construct(protected CompanyShareLinkService $shareLinks) {}

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
                        ->orWhere('primary_contact_phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(Pagination::perPage($request, 12))
            ->withQueryString()
            ->through(fn (Company $company) => $this->summarise($company, $request));

        return Inertia::render('TaskManagement/logo-library/index', [
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

        $company->load('shareLink');

        return Inertia::render('TaskManagement/logo-library/show', [
            'company' => $this->detail($company, $request),
            'variants' => CompanyLogoVariant::options(),
            'can' => [
                'manage' => $request->user()->can('manageLogos', $company),
                'share' => $request->user()->can('shareLogos', $company),
            ],
        ]);
    }

    public function update(CompanyLogoLibraryRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('manageLogos', $company);

        $company->update($request->validated());

        return back()->with('success', 'Company details updated.');
    }

    public function storeLogo(CompanyLogoUploadRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('manageLogos', $company);

        $variant = CompanyLogoVariant::from($request->validated('variant'));
        $file = $request->file('file');

        $company->getMedia('logos')
            ->filter(fn (Media $media) => ($media->getCustomProperty('variant') ?? null) === $variant->value)
            ->each->delete();

        $company->addMedia($file)
            ->withCustomProperties([
                'variant' => $variant->value,
                'uploaded_by_user_id' => $request->user()->id,
            ])
            ->toMediaCollection('logos');

        return back()->with('success', 'Logo uploaded.');
    }

    public function destroyLogo(Request $request, Company $company, Media $media): RedirectResponse
    {
        $this->assertLogoMedia($company, $media);
        $this->authorize('manageLogos', $company);

        $media->delete();

        return redirect()
            ->route('tasks.logo-library.show', $company)
            ->with('success', 'Logo removed.');
    }

    public function previewLogo(Request $request, Company $company, Media $media): SymfonyResponse
    {
        $this->assertLogoMedia($company, $media);
        $this->authorize('viewLogo', $company);
        $this->assertLogoFileExists($media);

        return $media->toInlineResponse($request);
    }

    public function downloadLogo(Request $request, Company $company, Media $media): SymfonyResponse
    {
        $this->assertLogoMedia($company, $media);
        $this->authorize('viewLogo', $company);
        $this->assertLogoFileExists($media);

        return $media->toResponse($request);
    }

    public function shareLink(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('shareLogos', $company);

        $link = $this->shareLinks->getOrCreate($company, $request->user());

        return back()->with('success', 'Share link ready.')->with('share_url', $link->publicUrl());
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(Company $company, Request $request): array
    {
        $company->loadMissing('media');

        return [
            'id' => $company->id,
            'name' => $company->name,
            'website' => $company->website,
            'email' => $company->primary_contact_email,
            'phone' => $company->primary_contact_phone,
            'logos' => $this->logoPayload($company, $request),
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
        $company->loadMissing('media');

        return [
            'id' => $company->id,
            'name' => $company->name,
            'website' => $company->website,
            'email' => $company->primary_contact_email,
            'phone' => $company->primary_contact_phone,
            'logos' => $this->logoPayload($company, $request),
            'share_url' => $company->shareLink?->publicUrl(),
        ];
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
                    'preview_url' => route('tasks.logo-library.logos.preview', [
                        'company' => $company,
                        'media' => $media->uuid,
                    ]),
                    'download_url' => route('tasks.logo-library.logos.download', [
                        'company' => $company,
                        'media' => $media->uuid,
                    ]),
                    'can_delete' => $request->user()->can('manageLogos', $company),
                ];
            })
            ->values()
            ->all();
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

    protected function assertLogoFileExists(Media $media): void
    {
        if (! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())) {
            abort(404);
        }
    }
}
