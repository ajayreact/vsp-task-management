<?php

namespace Database\Seeders\RecruiterOperations;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Additive, idempotent seed for Recruiter Operations access on databases that
 * already exist. RolesAndPermissionsSeeder covers fresh installs, but it
 * re-syncs every system role; this one never removes or resets a grant:
 *
 * - creates the recruiter.* permissions if missing
 * - creates the recruiter roles with their defaults only when first created,
 *   so later edits made in the role editor survive a re-run
 * - grants the recruiter.* permissions to Admin, which holds every ability
 */
class RecruiterRolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $recruiterAbilities = array_values(array_filter(
            Ability::cases(),
            fn (Ability $ability) => $ability->group() === 'Recruiter Operations',
        ));

        foreach ($recruiterAbilities as $ability) {
            Permission::findOrCreate($ability->value, 'web');
        }

        foreach ([SystemRole::Recruiter, SystemRole::RecruiterLead] as $systemRole) {
            $role = Role::query()
                ->where('name', $systemRole->value)
                ->where('guard_name', 'web')
                ->first();

            if ($role !== null) {
                continue;
            }

            Role::create(['name' => $systemRole->value, 'guard_name' => 'web'])->syncPermissions(
                array_map(fn (Ability $ability) => $ability->value, $systemRole->abilities())
            );
        }

        Role::query()
            ->where('name', SystemRole::Admin->value)
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo(array_map(fn (Ability $ability) => $ability->value, $recruiterAbilities));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
