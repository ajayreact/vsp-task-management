<?php

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContractStatus;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Services\ContractShareLinkService;

beforeEach(function () {
    $this->withoutVite();
});

test('a valid short code shows the contract to a guest', function () {
    $contract = Contract::factory()->create(['status' => ContractStatus::Generated]);
    $link = app(ContractShareLinkService::class)->getOrCreate($contract, User::factory()->create());

    $this->get(route('contract-share.short.show', ['shortCode' => $link->short_code]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/contract-share/show')
            ->where('contract.contract_number', $contract->contract_number)
            ->where('can_sign', true)
            ->missing('auth.user.id'));
});

test('viewing a contract share link marks it viewed and updates status', function () {
    $contract = Contract::factory()->create(['status' => ContractStatus::SentToClient]);
    $link = app(ContractShareLinkService::class)->getOrCreate($contract, User::factory()->create());

    $this->get(route('contract-share.short.show', ['shortCode' => $link->short_code]))
        ->assertOk();

    $contract->refresh();
    $link->refresh();

    expect($contract->status)->toBe(ContractStatus::Viewed)
        ->and($link->viewed_at)->not->toBeNull();
});

test('a client can sign a contract through the public link', function () {
    $employee = opsContractEmployee();
    $contract = Contract::factory()->create([
        'status' => ContractStatus::SentToClient,
        'created_by_user_id' => $employee->user->id,
    ]);
    $link = app(ContractShareLinkService::class)->getOrCreate($contract, $employee->user);

    $this->actingAs($employee->user)
        ->post("/tasks/contracts/{$contract->id}/generate-pdf")
        ->assertRedirect();

    $this->post(route('contract-share.short.sign', ['shortCode' => $link->short_code]), [
        'signer_name' => 'Jane Client',
        'authorized_person' => 'Jane Client',
        'signature_type' => 'typed',
        'signature_data' => 'Jane Client',
        'agreed' => '1',
    ])->assertRedirect();

    $contract->refresh();

    expect($contract->status)->toBe(ContractStatus::Signed)
        ->and($contract->signed_at)->not->toBeNull()
        ->and($contract->signatures()->count())->toBe(1)
        ->and($contract->currentVersion?->getFirstMedia('signed_pdf'))->not->toBeNull();
});

test('the public share url uses the short code route', function () {
    $contract = Contract::factory()->create();
    $link = app(ContractShareLinkService::class)->getOrCreate($contract, User::factory()->create());

    expect($link->publicUrl())->toEndWith('/ct/'.$link->short_code)
        ->and(route('contract-share.short.show', ['shortCode' => $link->short_code], false))->toBe('/ct/'.$link->short_code);
});

test('signed contracts cannot be signed again', function () {
    $contract = Contract::factory()->create(['status' => ContractStatus::Signed]);
    $link = app(ContractShareLinkService::class)->getOrCreate($contract, User::factory()->create());

    $this->post(route('contract-share.short.sign', ['shortCode' => $link->short_code]), [
        'signer_name' => 'Jane Client',
        'signature_type' => 'typed',
        'signature_data' => 'Jane Client',
        'agreed' => '1',
    ])->assertRedirect()->assertSessionHas('error');
});
