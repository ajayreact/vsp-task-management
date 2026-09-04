<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\CompanyDocumentCategory;
use App\Modules\TaskManagement\Enums\ContractCountry;
use App\Modules\TaskManagement\Enums\ContractEventType;
use App\Modules\TaskManagement\Enums\ContractStatus;
use App\Modules\TaskManagement\Enums\ContractVersionStatus;
use App\Modules\TaskManagement\Enums\ContractType;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Models\ContractSignature;
use App\Modules\TaskManagement\Models\ContractVersion;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractService
{
    public function __construct(
        protected ContractNumberGenerator $numbers,
        protected ContractEventLogger $events,
        protected ContractPdfService $pdf,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Contract
    {
        return DB::transaction(function () use ($user, $data): Contract {
            $company = Company::query()->findOrFail($data['tm_company_id']);
            $snapshot = $this->buildSnapshot($data, $company);

            $contract = Contract::query()->create([
                'contract_number' => $data['contract_number'] ?? $this->numbers->next(),
                'title' => $data['title'],
                'contract_type' => $data['contract_type'],
                'country' => $data['country'],
                'currency' => $data['currency'],
                'status' => ContractStatus::Draft,
                'tm_company_id' => $company->id,
                'effective_date' => $data['effective_date'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'created_by_user_id' => $user->id,
                'updated_by_user_id' => $user->id,
            ]);

            $version = $this->createVersion($contract, $user, $snapshot);

            $contract->update(['current_version_id' => $version->id]);
            $this->events->log($contract, ContractEventType::Created, $user);

            return $contract->fresh(['company', 'currentVersion', 'createdBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Contract $contract, User $user, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $user, $data): Contract {
            $company = Company::query()->findOrFail($data['tm_company_id']);
            $snapshot = $this->buildSnapshot($data, $company);

            $needsNewVersion = ! $contract->status->isEditable()
                || in_array($contract->status, [ContractStatus::SentToClient, ContractStatus::Viewed, ContractStatus::Generated], true);

            if ($needsNewVersion) {
                $contract->versions()
                    ->where('status', ContractVersionStatus::Active)
                    ->update(['status' => ContractVersionStatus::Superseded]);

                $version = $this->createVersion(
                    $contract,
                    $user,
                    $snapshot,
                    ($contract->versions()->max('version_number') ?? 0) + 1,
                    'Updated after contract was sent/generated',
                );

                $contract->update([
                    'current_version_id' => $version->id,
                    'status' => ContractStatus::Draft,
                ]);

                $this->events->log($contract, ContractEventType::VersionCreated, $user, [
                    'version_number' => $version->version_number,
                ]);
            } else {
                $contract->currentVersion?->update(['snapshot' => $snapshot]);
                $this->events->log($contract, ContractEventType::Edited, $user);
            }

            $contract->update([
                'title' => $data['title'],
                'contract_type' => $data['contract_type'],
                'country' => $data['country'],
                'currency' => $data['currency'],
                'tm_company_id' => $company->id,
                'effective_date' => $data['effective_date'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'updated_by_user_id' => $user->id,
            ]);

            return $contract->fresh(['company', 'currentVersion', 'createdBy']);
        });
    }

    public function generatePdf(Contract $contract, User $user, bool $signed = false): ContractVersion
    {
        $contract->loadMissing(['company', 'currentVersion']);
        $version = $contract->currentVersion ?? throw new \RuntimeException('Contract has no active version.');

        $rendered = $this->pdf->render(
            $contract,
            $version,
            includeSignaturePlaceholders: ! $signed,
            clientSignature: $signed
                ? $contract->signatures()->latest('signed_at')->first()?->only([
                    'signer_name', 'authorized_person', 'signature_type', 'signature_data', 'signed_at',
                ])
                : null,
        );

        $collection = $signed ? 'signed_pdf' : 'pdf';
        $version->clearMediaCollection($collection);
        $version->addMediaFromString($rendered['content'])
            ->usingFileName($rendered['filename'])
            ->withCustomProperties(['uploaded_by_user_id' => $user->id])
            ->toMediaCollection($collection);

        if (! $signed) {
            $contract->update(['status' => ContractStatus::Generated]);
        }

        $this->events->log($contract, ContractEventType::PdfGenerated, $user, [
            'signed' => $signed,
            'version_number' => $version->version_number,
        ]);

        return $version->refresh();
    }

    public function storeInOperationsDocuments(Contract $contract, User $user, bool $signed = false): CompanyDocument
    {
        $contract->loadMissing(['company', 'currentVersion']);
        $version = $contract->currentVersion ?? throw new \RuntimeException('Contract has no active version.');
        $collection = $signed ? 'signed_pdf' : 'pdf';
        $media = $version->getFirstMedia($collection);

        if ($media === null) {
            $this->generatePdf($contract, $user, $signed);
            $media = $version->refresh()->getFirstMedia($collection);
        }

        abort_if($media === null, 422, 'Generate the PDF before storing it in Operations Documents.');

        $title = sprintf('%s - %s%s', $contract->contract_number, $contract->company->name, $signed ? ' (Signed)' : '');

        $document = CompanyDocument::query()->create([
            'tm_company_id' => $contract->tm_company_id,
            'title' => $title,
            'category' => CompanyDocumentCategory::Contract,
            'description' => $contract->title,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);

        $path = $media->getPath();
        $document->addMedia($path)
            ->usingFileName($media->file_name)
            ->withCustomProperties([
                'uploaded_by_user_id' => $user->id,
                'tm_contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'signed' => $signed,
            ])
            ->toMediaCollection('file');

        if ($signed) {
            $contract->update(['signed_document_id' => $document->id]);
        } else {
            $contract->update(['original_document_id' => $document->id]);
        }

        $this->events->log($contract, ContractEventType::StoredInDocuments, $user, [
            'document_id' => $document->id,
            'signed' => $signed,
        ]);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function signByClient(Contract $contract, array $data, Request $request): Contract
    {
        return DB::transaction(function () use ($contract, $data, $request): Contract {
            abort_if($contract->status === ContractStatus::Signed, 422, 'This contract has already been signed.');

            $version = $contract->currentVersion ?? throw new \RuntimeException('Contract has no active version.');

            ContractSignature::query()->create([
                'tm_contract_id' => $contract->id,
                'tm_contract_version_id' => $version->id,
                'party' => 'client',
                'signer_name' => $data['signer_name'],
                'authorized_person' => $data['authorized_person'] ?? null,
                'signature_type' => $data['signature_type'],
                'signature_data' => $data['signature_data'],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'signed_at' => now(),
            ]);

            $contract->update([
                'status' => ContractStatus::Signed,
                'signed_at' => now(),
            ]);

            $this->events->log($contract, ContractEventType::Signed, actor: null, metadata: [
                'signer_name' => $data['signer_name'],
                'ip_address' => $request->ip(),
            ]);

            return $contract->fresh(['company', 'currentVersion', 'signatures']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildSnapshot(array $data, Company $company): array
    {
        $provider = $data['provider'] ?? config('contracts.provider');
        $client = $data['client'] ?? [];

        if (($client['name'] ?? '') === '') {
            $client['name'] = $company->name;
        }

        if (($client['email'] ?? '') === '' && $company->primary_contact_email) {
            $client['email'] = $company->primary_contact_email;
        }

        if (($client['phone'] ?? '') === '' && $company->primary_contact_phone) {
            $client['phone'] = $company->primary_contact_phone;
        }

        if (($client['website'] ?? '') === '' && $company->website) {
            $client['website'] = $company->website;
        }

        if (($client['authorized_person'] ?? '') === '' && $company->primary_contact_name) {
            $client['authorized_person'] = $company->primary_contact_name;
        }

        return [
            'provider' => $provider,
            'client' => $client,
            'service_plan' => $data['service_plan'] ?? [],
            'deliverables' => array_values($data['deliverables'] ?? []),
            'extra_work' => array_values($data['extra_work'] ?? []),
            'requirements' => array_values($data['requirements'] ?? self::defaultRequirements()),
            'responsibilities' => array_values($data['responsibilities'] ?? self::defaultResponsibilities()),
            'campaign_objective' => $data['campaign_objective'] ?? ['type' => 'lead_generation', 'custom' => ''],
            'client_content' => $data['client_content'] ?? ['items' => [], 'description' => ''],
            'lead_generation' => $data['lead_generation'] ?? [],
            'lead_pricing' => array_values($data['lead_pricing'] ?? []),
            'lead_example' => $data['lead_example'] ?? [],
            'payment_terms' => $data['payment_terms'] ?? self::defaultPaymentTerms(),
            'custom_terms' => $data['custom_terms'] ?? '',
            'document_logo' => $data['document_logo'] ?? '',
            'provider_signature' => $data['provider_signature'] ?? config('contracts.provider_signature', 'Ajay O'),
            'provider_signature_date' => $data['provider_signature_date'] ?? ($data['effective_date'] ?? now()->toDateString()),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    protected function createVersion(
        Contract $contract,
        User $user,
        array $snapshot,
        int $versionNumber = 1,
        ?string $changeSummary = null,
    ): ContractVersion {
        return ContractVersion::query()->create([
            'tm_contract_id' => $contract->id,
            'version_number' => $versionNumber,
            'status' => ContractVersionStatus::Active,
            'snapshot' => $snapshot,
            'change_summary' => $changeSummary,
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function defaultRequirements(): array
    {
        return [
            ['label' => 'Company / Brand Name', 'value' => ''],
            ['label' => 'Logo and brand materials', 'value' => ''],
            ['label' => 'Website / Landing Page URL', 'value' => ''],
            ['label' => 'Phone Number and Email', 'value' => ''],
            ['label' => 'Products / Services to be promoted', 'value' => ''],
            ['label' => 'Target Audience', 'value' => ''],
            ['label' => 'Target Location(s)', 'value' => ''],
            ['label' => 'Campaign Objective', 'value' => ''],
            ['label' => 'Required Lead Type', 'value' => ''],
            ['label' => 'Existing Photos / Videos / Marketing Materials', 'value' => ''],
        ];
    }

    /**
     * @return list<array{text: string}>
     */
    public static function defaultResponsibilities(): array
    {
        return [
            ['text' => 'Contacting generated leads'],
            ['text' => 'Following up with leads'],
            ['text' => 'Handling appointments / consultations'],
            ['text' => 'Converting leads into customers'],
            ['text' => 'Providing timely responses to enquiries'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultPaymentTerms(): array
    {
        return [
            'invoice_payment_period' => 'Lead invoice payment within 3 days from invoice date.',
            'advance_payment' => 'No advance payment for lead generation.',
            'non_payment_terms' => 'Services may be paused if payment is not received within the agreed period.',
            'other' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultFormPayload(?Company $company = null): array
    {
        $country = ContractCountry::India;

        return [
            'title' => ContractType::DigitalMarketingLeadGeneration->label(),
            'contract_number' => '',
            'contract_type' => ContractType::DigitalMarketingLeadGeneration->value,
            'country' => $country->value,
            'currency' => $country->defaultCurrency(),
            'effective_date' => now()->toDateString(),
            'start_date' => '',
            'end_date' => '',
            'tm_company_id' => $company?->id ? (string) $company->id : '',
            'provider' => config('contracts.provider'),
            'client' => [
                'name' => $company?->name ?? '',
                'authorized_person' => $company?->primary_contact_name ?? '',
                'phone' => $company?->primary_contact_phone ?? '',
                'email' => $company?->primary_contact_email ?? '',
                'website' => $company?->website ?? '',
                'address' => '',
            ],
            'service_plan' => [
                'monthly_fee' => '',
                'currency' => $country->defaultCurrency(),
                'billing_frequency' => 'monthly',
            ],
            'deliverables' => [],
            'extra_work' => [],
            'requirements' => self::defaultRequirements(),
            'responsibilities' => self::defaultResponsibilities(),
            'campaign_objective' => ['type' => 'lead_generation', 'custom' => ''],
            'client_content' => [
                'items' => [],
                'description' => '',
            ],
            'lead_generation' => [
                'lead_type' => '',
                'cpl' => '',
                'currency' => $country->defaultCurrency(),
                'qualification' => '',
                'notes' => '',
            ],
            'lead_pricing' => [],
            'lead_example' => [
                'quantity' => '',
                'cpl' => '',
                'currency' => $country->defaultCurrency(),
            ],
            'payment_terms' => self::defaultPaymentTerms(),
            'custom_terms' => '',
            'document_logo' => '',
            'provider_signature' => config('contracts.provider_signature', 'Ajay O'),
            'provider_signature_date' => now()->toDateString(),
        ];
    }
}
