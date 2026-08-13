<?php

use App\Modules\Core\Models\User;

test('guests are redirected away from the crm module', function () {
    $this->get('/crm')->assertRedirect('/login');
});

test('authenticated users can reach the crm module', function () {
    $this->actingAs(User::factory()->create())
        ->get('/crm')
        ->assertOk();
});

test('guests are redirected away from the client portal', function () {
    $this->get('/portal')->assertRedirect('/login');
});

test('the client portal is served from its own route group', function () {
    expect(route('portal.dashboard', absolute: false))->toBe('/portal')
        ->and(route('crm.dashboard', absolute: false))->toBe('/crm');
});
