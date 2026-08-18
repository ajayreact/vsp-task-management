<?php

namespace Database\Seeders\Core;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: safe to re-run after new abilities are added to the enum.
 * Hand-created permissions are left alone. Retired CRM, portal, and lead
 * permission names are detached from roles and deleted. The old `client`
 * portal role is removed.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Ability::cases() as $ability) {
            Permission::findOrCreate($ability->value, 'web');
        }

        $this->forgetRetiredPermissions();
        $this->forgetRetiredRoles();

        foreach (SystemRole::cases() as $systemRole) {
            $role = Role::findOrCreate($systemRole->value, 'web');

            $role->syncPermissions(
                array_map(fn (Ability $ability) => $ability->value, $systemRole->abilities())
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function forgetRetiredPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', Ability::retired())
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        }

        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function forgetRetiredRoles(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $ids = DB::table('roles')
            ->where('guard_name', 'web')
            ->where('name', 'client')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->whereIn('role_id', $ids)->delete();
        }

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('role_id', $ids)->delete();
        }

        DB::table('roles')->whereIn('id', $ids)->delete();
    }
}
