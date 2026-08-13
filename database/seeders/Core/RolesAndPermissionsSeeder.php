<?php

namespace Database\Seeders\Core;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: safe to re-run after new abilities are added to the enum.
 * Permissions absent from the enum are left alone rather than deleted, so a
 * permission created by hand in production is not silently dropped.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Ability::cases() as $ability) {
            Permission::findOrCreate($ability->value, 'web');
        }

        foreach (SystemRole::cases() as $systemRole) {
            $role = Role::findOrCreate($systemRole->value, 'web');

            $role->syncPermissions(
                array_map(fn (Ability $ability) => $ability->value, $systemRole->abilities())
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
