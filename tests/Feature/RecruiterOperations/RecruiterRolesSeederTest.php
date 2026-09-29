<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use Database\Seeders\RecruiterOperations\RecruiterRolesSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function recruiterAbilityNames(): array
{
    return collect(Ability::cases())
        ->filter(fn (Ability $ability) => $ability->group() === 'Recruiter Operations')
        ->map(fn (Ability $ability) => $ability->value)
        ->sort()
        ->values()
        ->all();
}

function rolePermissionNames(string $role): array
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return Role::findByName($role, 'web')->permissions()->pluck('name')->sort()->values()->all();
}

test('it creates the recruiter roles with their default abilities', function () {
    $this->seed(RecruiterRolesSeeder::class);

    expect(rolePermissionNames(SystemRole::Recruiter->value))->toBe([Ability::RecruiterAccess->value])
        ->and(rolePermissionNames(SystemRole::RecruiterLead->value))->toBe(recruiterAbilityNames());
});

test('it is idempotent', function () {
    $this->seed(RecruiterRolesSeeder::class);
    $this->seed(RecruiterRolesSeeder::class);

    expect(Role::query()->whereIn('name', [SystemRole::Recruiter->value, SystemRole::RecruiterLead->value])->count())->toBe(2)
        ->and(rolePermissionNames(SystemRole::RecruiterLead->value))->toBe(recruiterAbilityNames());
});

test('it keeps role editor changes made to an existing recruiter role', function () {
    $this->seed(RecruiterRolesSeeder::class);

    Role::findByName(SystemRole::RecruiterLead->value, 'web')->syncPermissions([Ability::RecruiterAccess->value]);

    $this->seed(RecruiterRolesSeeder::class);

    expect(rolePermissionNames(SystemRole::RecruiterLead->value))->toBe([Ability::RecruiterAccess->value]);
});

test('it adds recruiter abilities to admin without removing existing grants', function () {
    Role::findOrCreate(SystemRole::Admin->value, 'web')->syncPermissions([Ability::ViewEmployees->value]);

    $this->seed(RecruiterRolesSeeder::class);

    expect(rolePermissionNames(SystemRole::Admin->value))
        ->toBe(collect([Ability::ViewEmployees->value, ...recruiterAbilityNames()])->sort()->values()->all());
});

test('it leaves other roles untouched and creates no others', function () {
    Role::findOrCreate(SystemRole::Manager->value, 'web')->syncPermissions([Ability::AccessTasks->value]);

    $this->seed(RecruiterRolesSeeder::class);

    expect(rolePermissionNames(SystemRole::Manager->value))->toBe([Ability::AccessTasks->value])
        ->and(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(collect([SystemRole::Manager->value, SystemRole::Recruiter->value, SystemRole::RecruiterLead->value])->sort()->values()->all());
});
