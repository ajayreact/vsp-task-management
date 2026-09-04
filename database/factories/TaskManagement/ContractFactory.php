<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContractCountry;
use App\Modules\TaskManagement\Enums\ContractStatus;
use App\Modules\TaskManagement\Enums\ContractType;
use App\Modules\TaskManagement\Enums\ContractVersionStatus;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Models\ContractVersion;
use App\Modules\TaskManagement\Services\ContractNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_number' => app(ContractNumberGenerator::class)->next(),
            'title' => fake()->sentence(4),
            'contract_type' => ContractType::DigitalMarketingLeadGeneration,
            'country' => ContractCountry::India,
            'currency' => 'INR',
            'status' => ContractStatus::Draft,
            'tm_company_id' => Company::factory(),
            'effective_date' => now()->toDateString(),
            'created_by_user_id' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Contract $contract): void {
            $version = ContractVersion::query()->create([
                'tm_contract_id' => $contract->id,
                'version_number' => 1,
                'status' => ContractVersionStatus::Active,
                'snapshot' => ContractSnapshotFactory::minimal(),
                'created_by_user_id' => $contract->created_by_user_id,
            ]);

            $contract->update(['current_version_id' => $version->id]);
        });
    }
}

final class ContractSnapshotFactory
{
    /**
     * @return array<string, mixed>
     */
    public static function minimal(): array
    {
        $provider = config('contracts.provider');

        return [
            'provider' => $provider,
            'client' => [
                'name' => 'Sample Client',
                'authorized_person' => 'Jane Doe',
                'phone' => '+91 90000 00000',
                'email' => 'client@example.com',
                'website' => 'https://example.com',
                'address' => 'Client address',
            ],
            'service_plan' => [
                'monthly_fee' => 50000,
                'currency' => 'INR',
                'billing_frequency' => 'monthly',
            ],
            'deliverables' => [],
            'extra_work' => [],
            'requirements' => [],
            'responsibilities' => [],
            'campaign_objective' => ['type' => 'lead_generation', 'custom' => ''],
            'client_content' => ['items' => [], 'description' => ''],
            'lead_generation' => ['lead_type' => '', 'cpl' => null, 'currency' => 'INR', 'qualification' => '', 'notes' => ''],
            'lead_pricing' => [],
            'lead_example' => ['quantity' => null, 'cpl' => null, 'currency' => 'INR'],
            'payment_terms' => [
                'invoice_payment_period' => 'Lead invoice payment within 3 days from invoice date.',
                'advance_payment' => 'No advance payment for lead generation.',
                'non_payment_terms' => 'Services may be paused if payment is not received within the agreed period.',
                'other' => '',
            ],
            'custom_terms' => '',
            'document_logo' => '',
            'provider_signature' => config('contracts.provider_signature', 'Ajay O'),
            'provider_signature_date' => now()->toDateString(),
        ];
    }
}
