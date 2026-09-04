<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContractCountry;
use App\Modules\TaskManagement\Enums\ContractEventType;
use App\Modules\TaskManagement\Enums\ContractStatus;
use App\Modules\TaskManagement\Enums\ContractType;
use App\Modules\TaskManagement\Http\Requests\ContractRequest;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Models\ContractShareLink;
use App\Modules\TaskManagement\Models\ContractVersion;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Services\ContractEventLogger;
use App\Modules\TaskManagement\Services\ContractPdfService;
use App\Modules\TaskManagement\Services\ContractService;
use App\Modules\TaskManagement\Services\ContractShareLinkService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ContractController extends Controller
{
    public function __construct(
        protected ContractService $contracts,
        protected ContractShareLinkService $shareLinks,
        protected ContractPdfService $pdf,
        protected ContractEventLogger $events,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contract::class);

        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->value() ?: null;
        $clientId = $request->integer('client') ?: null;
        $contractType = $request->string('contract_type')->value() ?: null;
        $country = $request->string('country')->value() ?: null;
        $createdBy = $request->integer('created_by') ?: null;

        $contracts = Contract::query()
            ->with(['company:id,name', 'createdBy:id,name', 'currentVersion'])
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($clientId !== null, fn ($query) => $query->where('tm_company_id', $clientId))
            ->when($contractType !== null && $contractType !== '', fn ($query) => $query->where('contract_type', $contractType))
            ->when($country !== null && $country !== '', fn ($query) => $query->where('country', $country))
            ->when($createdBy !== null, fn ($query) => $query->where('created_by_user_id', $createdBy))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('contract_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(Pagination::perPage($request))
            ->withQueryString()
            ->through(fn (Contract $contract) => $this->rowPayload($contract, $request));

        $creators = User::query()
            ->whereIn('id', Contract::query()->distinct()->pluck('created_by_user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('TaskManagement/contracts/index', [
            'contracts' => $contracts,
            'clients' => Company::query()->orderBy('name')->get(['id', 'name']),
            'creators' => $creators,
            'statuses' => ContractStatus::options(),
            'contractTypes' => ContractType::options(),
            'countries' => ContractCountry::options(),
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'status' => $status,
                'client' => $clientId,
                'contract_type' => $contractType,
                'country' => $country,
                'created_by' => $createdBy,
            ],
            'can' => [
                'manage' => $request->user()->can('create', Contract::class),
                'share' => $request->user()->can(Ability::ShareContracts->value),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Contract::class);

        $company = null;
        $clientId = $request->integer('client') ?: null;

        if ($clientId !== null) {
            $company = Company::query()->find($clientId);
        }

        return Inertia::render('TaskManagement/contracts/create', [
            'clients' => $this->clientOptions(),
            'contractTypes' => ContractType::options(),
            'countries' => ContractCountry::options(),
            'defaults' => ContractService::defaultFormPayload($company),
            'suggestedNumber' => app(\App\Modules\TaskManagement\Services\ContractNumberGenerator::class)->next(),
        ]);
    }

    public function store(ContractRequest $request): RedirectResponse
    {
        $this->authorize('create', Contract::class);

        $data = $request->validated();

        if (empty($data['contract_number'])) {
            unset($data['contract_number']);
        }

        $contract = $this->contracts->create($request->user(), $data);

        return redirect()
            ->route('tasks.contracts.show', $contract)
            ->with('success', 'Contract draft saved.');
    }

    public function show(Request $request, Contract $contract): Response
    {
        $this->authorize('view', $contract);

        if ($contract->current_version_id) {
            $this->contracts->compactVersionSnapshotLogo(
                ContractVersion::query()->find($contract->current_version_id)
            );
        }

        $contract->load([
            'company:id,name,primary_contact_name,primary_contact_email,primary_contact_phone,website',
            'createdBy:id,name',
            'currentVersion.media',
            'versions' => fn ($q) => $q
                ->select([
                    'id',
                    'tm_contract_id',
                    'version_number',
                    'status',
                    'change_summary',
                    'created_by_user_id',
                    'created_at',
                    'updated_at',
                ])
                ->with(['createdBy:id,name', 'media'])
                ->limit(10),
            'shareLink',
            'signatures' => fn ($q) => $q->select([
                'id',
                'tm_contract_id',
                'tm_contract_version_id',
                'party',
                'signer_name',
                'authorized_person',
                'signature_type',
                'ip_address',
                'user_agent',
                'signed_at',
                'created_at',
                'updated_at',
            ]),
            'events' => fn ($q) => $q->with('actor:id,name')->limit(50),
            'originalDocument:id,title',
            'signedDocument:id,title',
        ]);

        return Inertia::render('TaskManagement/contracts/show', [
            'contract' => $this->detailPayload($contract, $request),
            'can' => $this->actionPermissions($request, $contract),
        ]);
    }

    public function edit(Request $request, Contract $contract): Response
    {
        $this->authorize('update', $contract);

        if ($contract->current_version_id) {
            $this->contracts->compactVersionSnapshotLogo(
                ContractVersion::query()->find($contract->current_version_id)
            );
        }

        $contract->load(['company:id,name', 'currentVersion.media']);

        return Inertia::render('TaskManagement/contracts/edit', [
            'contract' => $this->editPayload($contract),
            'clients' => $this->clientOptions(),
            'contractTypes' => ContractType::options(),
            'countries' => ContractCountry::options(),
        ]);
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $this->contracts->update($contract, $request->user(), $request->validated());

        return redirect()
            ->route('tasks.contracts.show', $contract)
            ->with('success', 'Contract updated.');
    }

    public function preview(Request $request, Contract $contract): Response
    {
        $this->authorize('view', $contract);

        if ($contract->current_version_id) {
            $this->contracts->compactVersionSnapshotLogo(
                ContractVersion::query()->find($contract->current_version_id)
            );
        }

        $contract->load(['company:id,name', 'currentVersion.media', 'createdBy:id,name']);

        return Inertia::render('TaskManagement/contracts/preview', [
            'contract' => $this->previewPayload($contract, $request),
            'can' => $this->actionPermissions($request, $contract),
        ]);
    }

    public function generatePdf(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('generatePdf', $contract);

        $this->contracts->generatePdf($contract, $request->user());

        return back()->with('success', 'PDF generated successfully.');
    }

    public function downloadPdf(Request $request, Contract $contract): SymfonyResponse
    {
        $this->authorize('view', $contract);

        $contract->loadMissing(['company', 'currentVersion']);
        $version = $contract->currentVersion ?? abort(404);

        $signed = $request->boolean('signed') && $contract->status === ContractStatus::Signed;

        $this->events->log($contract, ContractEventType::Downloaded, $request->user(), [
            'signed' => $signed,
        ]);

        if ($signed) {
            $media = $version->getFirstMedia('signed_pdf');

            if ($media !== null && is_file($media->getPath())) {
                return response()->download($media->getPath(), $media->file_name, [
                    'Content-Type' => $media->mime_type,
                ]);
            }
        }

        $media = $version->getFirstMedia('pdf');

        if ($media !== null && is_file($media->getPath())) {
            return response()->download($media->getPath(), $media->file_name, [
                'Content-Type' => $media->mime_type,
            ]);
        }

        return $this->pdf->downloadResponse($contract, $version, $signed);
    }

    public function inlinePdf(Request $request, Contract $contract): SymfonyResponse
    {
        $this->authorize('view', $contract);

        $contract->loadMissing(['company', 'currentVersion']);
        $version = $contract->currentVersion ?? abort(404);

        $signed = $request->boolean('signed') && $contract->status === ContractStatus::Signed;
        $media = $version->getFirstMedia($signed ? 'signed_pdf' : 'pdf');

        if ($media !== null && is_file($media->getPath())) {
            return $media->toInlineResponse($request);
        }

        return $this->pdf->inlineResponse($contract, $version);
    }

    public function logo(Request $request, Contract $contract): SymfonyResponse
    {
        $this->authorize('view', $contract);

        if ($contract->current_version_id) {
            $this->contracts->compactVersionSnapshotLogo(
                ContractVersion::query()->find($contract->current_version_id)
            );
        }

        $contract->loadMissing(['currentVersion.media']);
        $media = $contract->currentVersion?->getFirstMedia('document_logo');

        if ($media !== null && is_file($media->getPath())) {
            return response()->file($media->getPath(), [
                'Content-Type' => $media->mime_type,
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }

        $defaultLogoPath = public_path((string) config('contracts.default_logo', 'images/branding/logo.png'));

        if (is_file($defaultLogoPath)) {
            return response()->file($defaultLogoPath, [
                'Content-Type' => mime_content_type($defaultLogoPath) ?: 'image/png',
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }

        abort(404);
    }

    public function shareLink(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('share', $contract);

        $validated = $request->validate([
            'expiry_preset' => ['nullable', 'string', Rule::in(['7_days', '15_days', '30_days', 'never'])],
        ]);

        $preset = $validated['expiry_preset'] ?? '30_days';
        $link = $this->shareLinks->getOrCreate($contract, $request->user(), $preset);

        if (in_array($contract->status, [ContractStatus::Draft, ContractStatus::Generated], true)) {
            $contract->update(['status' => ContractStatus::SentToClient]);
            $this->events->log($contract, ContractEventType::Sent, $request->user());
        }

        $this->events->log($contract, ContractEventType::SigningLinkGenerated, $request->user(), [
            'expiry_preset' => $preset,
        ]);

        $shareUrl = $this->shareLinkUrl($link);
        $shareMessage = "{$contract->title}\n\nReview & Sign:\n{$shareUrl}";

        return back()
            ->with('success', 'Signing link ready.')
            ->with('share_url', $shareUrl)
            ->with('share_message', $shareMessage);
    }

    public function storeInDocuments(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('generatePdf', $contract);

        $signed = $request->boolean('signed');
        $document = $this->contracts->storeInOperationsDocuments($contract, $request->user(), $signed);

        return back()->with('success', $signed
            ? 'Signed contract stored in Operations Documents.'
            : 'Contract stored in Operations Documents.');
    }

    public function cancel(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        abort_if($contract->status === ContractStatus::Signed, 422, 'Signed contracts cannot be cancelled.');

        $contract->update(['status' => ContractStatus::Cancelled]);
        $this->events->log($contract, ContractEventType::Cancelled, $request->user());

        return back()->with('success', 'Contract cancelled.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowPayload(Contract $contract, Request $request): array
    {
        $version = $contract->currentVersion;
        $hasPdf = $version?->getFirstMedia('pdf') !== null;

        return [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'title' => $contract->title,
            'contract_type' => $contract->contract_type->value,
            'contract_type_label' => $contract->contract_type->label(),
            'country' => $contract->country->value,
            'country_label' => $contract->country->label(),
            'currency' => $contract->currency,
            'status' => $contract->status->value,
            'status_label' => $contract->status->label(),
            'status_variant' => $contract->status->badgeVariant(),
            'client' => [
                'id' => $contract->company->id,
                'name' => $contract->company->name,
            ],
            'effective_date' => $contract->effective_date->toDateString(),
            'created_at' => $contract->created_at->toIso8601String(),
            'signed_at' => $contract->signed_at?->toIso8601String(),
            'created_by' => $contract->createdBy?->name ?? 'System',
            'has_pdf' => $hasPdf,
            'urls' => [
                'show' => route('tasks.contracts.show', $contract),
                'edit' => route('tasks.contracts.edit', $contract),
                'preview' => route('tasks.contracts.preview', $contract),
                'download' => route('tasks.contracts.download', $contract),
            ],
            'can' => [
                'update' => $request->user()->can('update', $contract),
                'share' => $request->user()->can('share', $contract),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function detailPayload(Contract $contract, Request $request): array
    {
        $version = $contract->currentVersion;
        $snapshot = $version?->snapshot ?? [];
        $pdfMedia = $version?->getFirstMedia('pdf');
        $signedPdfMedia = $version?->getFirstMedia('signed_pdf');
        $shareLink = $contract->shareLink;
        $latestSignature = $contract->signatures->sortByDesc('signed_at')->first();
        $hasDocumentLogo = (bool) $version?->hasMedia('document_logo');

        return [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'title' => $contract->title,
            'contract_type' => $contract->contract_type->value,
            'contract_type_label' => $contract->contract_type->label(),
            'country' => $contract->country->value,
            'country_label' => $contract->country->label(),
            'currency' => $contract->currency,
            'status' => $contract->status->value,
            'status_label' => $contract->status->label(),
            'status_variant' => $contract->status->badgeVariant(),
            'effective_date' => $contract->effective_date->toDateString(),
            'start_date' => $contract->start_date?->toDateString(),
            'end_date' => $contract->end_date?->toDateString(),
            'created_at' => $contract->created_at->toIso8601String(),
            'signed_at' => $contract->signed_at?->toIso8601String(),
            'client' => [
                'id' => $contract->company->id,
                'name' => $contract->company->name,
            ],
            'created_by' => $contract->createdBy?->name ?? 'System',
            'has_document_logo' => $hasDocumentLogo,
            'logo_url' => $hasDocumentLogo ? route('tasks.contracts.logo', $contract) : null,
            'provider_signature' => $snapshot['provider_signature'] ?? config('contracts.provider_signature', 'Ajay O'),
            'provider_signature_date' => $snapshot['provider_signature_date'] ?? $contract->effective_date->toDateString(),
            'version_number' => $version?->version_number,
            'pdf' => $this->mediaPayload($contract, $pdfMedia, false),
            'signed_pdf' => $this->mediaPayload($contract, $signedPdfMedia, true),
            'share_link' => $this->shareLinkPayload($shareLink),
            'signature' => $latestSignature ? [
                'signer_name' => $latestSignature->signer_name,
                'authorized_person' => $latestSignature->authorized_person,
                'signature_type' => $latestSignature->signature_type,
                'signed_at' => $latestSignature->signed_at->toIso8601String(),
                'ip_address' => $latestSignature->ip_address,
            ] : null,
            'documents' => [
                'original' => $contract->originalDocument ? [
                    'id' => $contract->originalDocument->id,
                    'title' => $contract->originalDocument->title,
                    'url' => $this->documentsIndexUrl($contract->contract_number),
                ] : null,
                'signed' => $contract->signedDocument ? [
                    'id' => $contract->signedDocument->id,
                    'title' => $contract->signedDocument->title,
                    'url' => $this->documentsIndexUrl($contract->contract_number),
                ] : null,
            ],
            'versions' => $contract->versions->map(fn ($v) => [
                'id' => $v->id,
                'version_number' => $v->version_number,
                'status' => $v->status->value,
                'change_summary' => $v->change_summary,
                'created_at' => $v->created_at->toIso8601String(),
                'created_by' => $v->createdBy?->name,
                'has_pdf' => $v->relationLoaded('media')
                    ? $v->media->contains(fn ($media) => $media->collection_name === 'pdf')
                    : $v->getFirstMedia('pdf') !== null,
            ])->values(),
            'timeline' => $contract->events->map(function ($event) {
                $label = $event->description ?? 'Activity';

                if ($label === 'Activity' && $event->event !== null) {
                    try {
                        $label = $event->event->label();
                    } catch (\Throwable) {
                        $label = $event->description ?? 'Activity';
                    }
                }

                return [
                    'id' => $event->id,
                    'label' => $label,
                    'by' => $event->actor?->name,
                    'at' => ($event->occurred_at ?? $event->created_at)->toIso8601String(),
                ];
            })->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function editPayload(Contract $contract): array
    {
        $snapshot = $contract->currentVersion?->snapshot ?? ContractService::defaultFormPayload($contract->company);
        unset($snapshot['document_logo']);

        $hasDocumentLogo = (bool) $contract->currentVersion?->hasMedia('document_logo');

        return [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'title' => $contract->title,
            'contract_type' => $contract->contract_type->value,
            'country' => $contract->country->value,
            'currency' => $contract->currency,
            'effective_date' => $contract->effective_date->toDateString(),
            'start_date' => $contract->start_date?->toDateString() ?? '',
            'end_date' => $contract->end_date?->toDateString() ?? '',
            'tm_company_id' => (string) $contract->tm_company_id,
            'status' => $contract->status->value,
            'has_document_logo' => $hasDocumentLogo,
            'logo_url' => $hasDocumentLogo ? route('tasks.contracts.logo', $contract) : null,
            'document_logo' => '',
            ...$snapshot,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function previewPayload(Contract $contract, Request $request): array
    {
        return [
            ...$this->detailPayload($contract, $request),
            'pdf_preview_url' => route('tasks.contracts.pdf', $contract),
        ];
    }

    /**
     * @return array<string, bool>
     */
    protected function actionPermissions(Request $request, Contract $contract): array
    {
        return [
            'update' => $request->user()->can('update', $contract),
            'share' => $request->user()->can('share', $contract),
            'generate_pdf' => $request->user()->can('generatePdf', $contract),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function clientOptions()
    {
        return Company::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'primary_contact_name',
                'primary_contact_email',
                'primary_contact_phone',
                'website',
            ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function shareLinkPayload(?ContractShareLink $link): ?array
    {
        if ($link === null) {
            return null;
        }

        return [
            'url' => $this->shareLinkUrl($link),
            'expires_at' => $link->expires_at?->toIso8601String(),
            'viewed_at' => $link->viewed_at?->toIso8601String(),
            'expiry_preset' => $link->expiry_preset,
        ];
    }

    protected function shareLinkUrl(ContractShareLink $link): string
    {
        if ($link->short_code !== null && $link->short_code !== '') {
            return url('/ct/'.$link->short_code);
        }

        return url('/contract-share/'.$link->token);
    }

    protected function documentsIndexUrl(string $contractNumber): string
    {
        if (Route::has('tasks.documents.index')) {
            return route('tasks.documents.index', ['search' => $contractNumber]);
        }

        return url('/tasks/documents?search='.urlencode($contractNumber));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function mediaPayload(Contract $contract, ?Media $media, bool $signed): ?array
    {
        if ($media === null) {
            return null;
        }

        return [
            'uuid' => $media->uuid,
            'name' => $media->file_name,
            'preview_url' => route('tasks.contracts.pdf', ['contract' => $contract, 'signed' => $signed ? 1 : 0]),
            'download_url' => route('tasks.contracts.download', ['contract' => $contract, 'signed' => $signed ? 1 : 0]),
        ];
    }
}
