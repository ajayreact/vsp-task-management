<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Spatie caches the permission table, and that cache outlives the
        // database rollback between tests.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Every real database has the full catalogue because the seeder puts
        // it there. Roles are left to each test to create.
        $now = now();

        Permission::insertOrIgnore(array_map(fn (Ability $ability) => [
            'name' => $ability->value,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ], Ability::cases()));
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A staff user holding exactly the abilities named, and nothing else. Passing
 * no abilities gives an account that can sign in but reach nothing.
 */
function staffWith(Ability ...$abilities): User
{
    $user = User::factory()->create();

    $user->syncPermissions(array_map(fn (Ability $ability) => $ability->value, $abilities));

    return $user;
}

/**
 * A user who passes every check, for tests about behaviour rather than access.
 */
function superAdmin(): User
{
    return User::factory()->create()->syncRoles(
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web')
    );
}

/**
 * A staff user who also has an employee profile, which is what task work
 * requires: you cannot be assigned a task without one.
 */
function employeeWith(Ability ...$abilities): Employee
{
    $employee = Employee::factory()->create();

    $employee->user->syncPermissions(array_map(fn (Ability $ability) => $ability->value, $abilities));

    return $employee;
}

/**
 * Channel callbacks register on the boot-time driver (null in phpunit).
 * Auth tests need Reverb with those callbacks on the active connection.
 */
function configureReverbForChannelAuth(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options' => [
            'host' => '127.0.0.1',
            'port' => 8080,
            'scheme' => 'http',
            'useTLS' => false,
        ],
    ]);

    app()->forgetInstance(\Illuminate\Broadcasting\BroadcastManager::class);
    app()->forgetInstance(\Illuminate\Contracts\Broadcasting\Factory::class);
    \Illuminate\Support\Facades\Broadcast::swap(new \Illuminate\Broadcasting\BroadcastManager(app()));

    require base_path('routes/channels.php');
}
