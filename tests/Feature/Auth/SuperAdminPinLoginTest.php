<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutVite();

    config([
        'auth.super_admin_pin.hash' => Hash::make('951570'),
        'auth.super_admin_pin.max_attempts' => 5,
        'auth.super_admin_pin.decay_seconds' => 60,
        'auth.super_admin_pin.pin_length' => 6,
    ]);
});

test('login page exposes pin login when a hash is configured', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/auth/login')
            ->where('superAdminPinLogin.enabled', true)
            ->where('superAdminPinLogin.pinLength', 6)
            ->missing('superAdminPinLogin.hash')
            ->missing('superAdminPinLogin.pin'));
});

test('login page hides pin login when no hash is configured', function () {
    config(['auth.super_admin_pin.hash' => null]);

    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/auth/login')
            ->where('superAdminPinLogin.enabled', false));
});

test('correct super admin pin authenticates the super admin session', function () {
    $admin = User::factory()->create(['is_active' => true])->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );

    Log::spy();

    $response = $this->post(route('login.super-admin-pin'), [
        'pin' => '951570',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($admin);
    expect($admin->fresh()->last_login_at)->not->toBeNull();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) => $message === 'auth.super_admin_pin_succeeded'
            && ($context['user_id'] ?? null) === $admin->id
            && ($context['success'] ?? null) === true
            && ! array_key_exists('pin', $context));
});

test('incorrect pin is rejected with a clear error and stays guest', function () {
    User::factory()->create(['is_active' => true])->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );

    Log::spy();

    $response = $this->from('/login')->post(route('login.super-admin-pin'), [
        'pin' => '000000',
    ]);

    $response->assertRedirect('/login')
        ->assertSessionHasErrors(['pin' => 'Invalid PIN. Please try again.']);
    $this->assertGuest();

    $content = session('errors')->getBag('default')->first('pin');
    expect($content)->toBe('Invalid PIN. Please try again.')
        ->and($content)->not->toContain('951570')
        ->and($content)->not->toContain(config('auth.super_admin_pin.hash'));

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context) => $message === 'auth.super_admin_pin_failed'
            && ($context['success'] ?? null) === false
            && ! array_key_exists('pin', $context));
});

test('pin must be exactly six digits', function () {
    User::factory()->create(['is_active' => true])->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );

    $this->from('/login')->post(route('login.super-admin-pin'), [
        'pin' => '95157',
    ])->assertSessionHasErrors(['pin' => 'Invalid PIN. Please try again.']);

    $this->assertGuest();
});

test('pin login never authenticates a normal staff user', function () {
    Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');

    User::factory()->create(['is_active' => true])->syncRoles(
        Role::findOrCreate(SystemRole::Employee->value, 'web')
    );

    $this->from('/login')->post(route('login.super-admin-pin'), [
        'pin' => '951570',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors('pin');

    $this->assertGuest();
});

test('pin login fails when hash is not configured', function () {
    config(['auth.super_admin_pin.hash' => null]);

    User::factory()->create(['is_active' => true])->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );

    $this->from('/login')->post(route('login.super-admin-pin'), [
        'pin' => '951570',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors(['pin' => 'Invalid PIN. Please try again.']);

    $this->assertGuest();
});

test('pin login is rate limited after repeated failures', function () {
    User::factory()->create(['is_active' => true])->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );

    RateLimiter::clear(Str::transliterate('super-admin-pin|127.0.0.1'));

    for ($i = 0; $i < 5; $i++) {
        $this->from('/login')->post(route('login.super-admin-pin'), [
            'pin' => '000000',
        ])->assertSessionHasErrors('pin');
    }

    $this->from('/login')->post(route('login.super-admin-pin'), [
        'pin' => '951570',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors('pin');

    $this->assertGuest();
});

test('pin is not leaked in the login page or failed pin response', function () {
    User::factory()->create(['is_active' => true])->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );

    $loginHtml = $this->get('/login')->assertOk()->getContent();
    expect($loginHtml)
        ->not->toContain('951570')
        ->not->toContain((string) config('auth.super_admin_pin.hash'));

    $this->from('/login')->post(route('login.super-admin-pin'), [
        'pin' => '000000',
    ]);

    expect(session()->all())
        ->not->toContain('951570');
});

test('email password login still works alongside pin login', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('inactive super admin cannot authenticate with pin', function () {
    User::factory()->create(['is_active' => false])->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );

    $this->from('/login')->post(route('login.super-admin-pin'), [
        'pin' => '951570',
    ])->assertSessionHasErrors('pin');

    $this->assertGuest();
});

test('logout clears the pin authenticated session', function () {
    $admin = User::factory()->create(['is_active' => true])->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );

    $this->post(route('login.super-admin-pin'), ['pin' => '951570'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($admin);

    $this->post(route('logout'))
        ->assertRedirect('/');
    $this->assertGuest();
});
