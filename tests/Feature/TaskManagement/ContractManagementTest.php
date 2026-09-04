<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\CompanyDocumentCategory;
use App\Modules\TaskManagement\Enums\ContractCountry;
use App\Modules\TaskManagement\Enums\ContractStatus;
use App\Modules\TaskManagement\Enums\ContractType;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Services\ContractShareLinkService;

beforeEach(function () {
    $this->withoutVite();
});

function opsContractEmployee(): Employee
{
    $department = Department::factory()->create(['code' => 'OPS']);
    $employee = Employee::factory()->for($department)->create();
    $employee->user->syncPermissions([
        Ability::AccessTasks->value,
        Ability::ViewContracts->value,
        Ability::ManageContracts->value,
        Ability::ShareContracts->value,
    ]);

    return $employee;
}

function validContractPayload(Company $company): array
{
    return [
        'title' => 'Digital Marketing Agreement',
        'contract_type' => ContractType::DigitalMarketingLeadGeneration->value,
        'country' => ContractCountry::India->value,
        'currency' => 'INR',
        'effective_date' => now()->toDateString(),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'tm_company_id' => $company->id,
        'provider' => [
            'name' => 'VSP Solutions',
            'authorized_person' => 'Operations Head',
            'phone' => '+91 90000 00000',
            'email' => 'contracts@vsp.test',
            'website' => 'https://vsp.test',
            'address' => 'Test address',
        ],
        'client' => [
            'name' => $company->name,
            'authorized_person' => 'Jane Doe',
            'phone' => '+91 91111 11111',
            'email' => 'client@example.com',
            'website' => 'https://example.com',
            'address' => 'Client address',
        ],
        'service_plan' => [
            'monthly_fee' => '50000',
            'currency' => 'INR',
            'billing_frequency' => 'monthly',
        ],
        'deliverables' => [
            ['quantity' => 2, 'name' => 'Promotional Videos', 'description' => 'Monthly videos'],
        ],
        'extra_work' => [],
        'requirements' => [],
        'responsibilities' => [],
        'campaign_objective' => ['type' => 'lead_generation', 'custom' => ''],
        'client_content' => ['items' => [], 'description' => ''],
        'lead_generation' => ['lead_type' => '', 'cpl' => '', 'currency' => 'INR', 'qualification' => '', 'notes' => ''],
        'lead_pricing' => [
            ['lead_type' => 'Basic Lead', 'cpl' => 350, 'description' => 'Name + Phone + Email'],
        ],
        'lead_example' => ['quantity' => 50, 'cpl' => 350, 'currency' => 'INR'],
        'payment_terms' => [
            'invoice_payment_period' => 'Lead invoice payment within 3 days from invoice date.',
            'advance_payment' => 'No advance payment for lead generation.',
            'non_payment_terms' => 'Services may be paused if payment is not received within the agreed period.',
            'other' => '',
        ],
        'custom_terms' => '',
    ];
}

test('authorized users can view the contracts index', function () {
    Contract::factory()->create(['title' => 'Sample Agreement']);

    $this->actingAs(opsContractEmployee()->user)
        ->get('/tasks/contracts')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/contracts/index')
            ->where('contracts.data.0.title', 'Sample Agreement'));
});

test('authorized users can create a contract draft', function () {
    $company = Company::factory()->create();
    $employee = opsContractEmployee();

    $this->actingAs($employee->user)
        ->post('/tasks/contracts', validContractPayload($company))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $contract = Contract::query()->sole();

    expect($contract->title)->toBe('Digital Marketing Agreement')
        ->and($contract->status)->toBe(ContractStatus::Draft)
        ->and($contract->contract_number)->toStartWith('VSP-CONTRACT-')
        ->and($contract->currentVersion)->not->toBeNull();
});

test('authorized users can generate a contract pdf', function () {
    $contract = Contract::factory()->create();
    $employee = opsContractEmployee();

    $this->actingAs($employee->user)
        ->post("/tasks/contracts/{$contract->id}/generate-pdf")
        ->assertRedirect();

    $contract->refresh();

    expect($contract->status)->toBe(ContractStatus::Generated)
        ->and($contract->currentVersion?->getFirstMedia('pdf'))->not->toBeNull();
});

test('share link generation updates status and returns share message', function () {
    $contract = Contract::factory()->create(['status' => ContractStatus::Generated]);
    $employee = opsContractEmployee();

    $response = $this->actingAs($employee->user)
        ->post("/tasks/contracts/{$contract->id}/share-link", ['expiry_preset' => '30_days']);

    $response->assertRedirect();
    $contract->refresh();
    $link = app(ContractShareLinkService::class)->getOrCreate($contract, $employee->user);

    expect($contract->status)->toBe(ContractStatus::SentToClient)
        ->and($response->getSession()->get('share_message'))->toContain($contract->title)
        ->and($response->getSession()->get('share_message'))->toContain('Review & Sign:')
        ->and($response->getSession()->get('share_url'))->toBe($link->publicUrl());
});

test('store in operations documents creates a contract category document', function () {
    $contract = Contract::factory()->create();
    $employee = opsContractEmployee();

    $this->actingAs($employee->user)
        ->post("/tasks/contracts/{$contract->id}/generate-pdf")
        ->assertRedirect();

    $this->actingAs($employee->user)
        ->post("/tasks/contracts/{$contract->id}/store-in-documents")
        ->assertRedirect();

    $contract->refresh();
    $document = CompanyDocument::query()->sole();

    expect($document->category)->toBe(CompanyDocumentCategory::Contract)
        ->and($contract->original_document_id)->toBe($document->id)
        ->and($document->getFirstMedia('file'))->not->toBeNull();
});

test('unauthorized users cannot access contracts', function () {
    Contract::factory()->create();

    $employee = employeeWith(Ability::AccessTasks);
    $employee->department->update(['code' => 'FIN']);

    $this->actingAs($employee->user)
        ->get('/tasks/contracts')
        ->assertForbidden();
});
