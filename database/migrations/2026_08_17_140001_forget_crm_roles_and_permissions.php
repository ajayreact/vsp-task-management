<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Forget leftover CRM/portal permission rows and the `client` role after those
 * abilities and SystemRole::Client were removed from Core. Does not alter
 * previously executed migrations or Task Management tables.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    protected array $retiredPermissions = [
        'crm.access',
        'portal.access',
        'crm.clients.view',
        'crm.clients.manage',
        'crm.campaigns.view',
        'crm.campaigns.manage',
        'crm.pipelines.manage',
        'crm.deals.view',
        'crm.deals.manage',
        'crm.reports.view',
        'crm.leads.view',
        'crm.leads.manage',
        'crm.leads.assign',
        'crm.integrations.manage',
    ];

    public function up(): void
    {
        $this->forgetRetiredPermissions();
        $this->forgetClientRole();
        $this->normaliseStaffUserType();
    }

    public function down(): void
    {
        // Irreversible identity cleanup.
    }

    protected function forgetRetiredPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->retiredPermissions)
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

    protected function forgetClientRole(): void
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

    protected function normaliseStaffUserType(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'user_type')) {
            return;
        }

        DB::table('users')
            ->where('user_type', 'client')
            ->update(['user_type' => 'internal']);
    }
};
