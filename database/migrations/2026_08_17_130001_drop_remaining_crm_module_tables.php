<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\PermissionRegistrar;

/**
 * Drop remaining CRM & Campaign Management schema after Lead Management was
 * already removed. Does not alter previously executed migrations.
 *
 * Does not touch tm_*, employees, departments, designations, staff users,
 * notifications (table), or Task Management media.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    protected array $crmActivitySubjects = [
        'App\\Modules\\Crm\\Models\\Client',
        'App\\Modules\\Crm\\Models\\Contact',
        'App\\Modules\\Crm\\Models\\ConnectedAccount',
        'App\\Modules\\Crm\\Models\\Campaign',
        'App\\Modules\\Crm\\Models\\CampaignProof',
        'App\\Modules\\Crm\\Models\\Deal',
        'App\\Modules\\Crm\\Models\\DealStageHistory',
        'App\\Modules\\Crm\\Models\\Pipeline',
        'App\\Modules\\Crm\\Models\\PipelineStage',
    ];

    /**
     * @var list<string>
     */
    protected array $crmPermissionNames = [
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
        $this->forgetCampaignProofMedia();
        $this->forgetPortalUsers();
        $this->dropUsersCrmClientId();

        Schema::dropIfExists('crm_campaign_proofs');
        Schema::dropIfExists('crm_deal_stage_history');
        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('crm_pipeline_stages');
        Schema::dropIfExists('crm_pipelines');
        Schema::dropIfExists('crm_campaigns');
        Schema::dropIfExists('crm_connected_accounts');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('crm_clients');

        $this->forgetCrmActivity();
        $this->forgetCrmPermissions();
    }

    public function down(): void
    {
        // Irreversible: restore from the pre-drop SQL backup.
    }

    protected function forgetCampaignProofMedia(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        Media::query()
            ->where('model_type', 'App\\Modules\\Crm\\Models\\CampaignProof')
            ->get()
            ->each(fn (Media $media) => $media->delete());
    }

    protected function forgetPortalUsers(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $staffUserIds = Schema::hasTable('employees')
            ? DB::table('employees')->whereNotNull('user_id')->pluck('user_id')
            : collect();

        $ids = DB::table('users')
            ->where('user_type', 'client')
            ->when($staffUserIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $staffUserIds))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->where('model_type', 'App\\Modules\\Core\\Models\\User')->whereIn('model_id', $ids)->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->where('model_type', 'App\\Modules\\Core\\Models\\User')->whereIn('model_id', $ids)->delete();
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->whereIn('user_id', $ids)->delete();
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', 'App\\Modules\\Core\\Models\\User')
                ->whereIn('notifiable_id', $ids)
                ->delete();
        }

        DB::table('users')->whereIn('id', $ids)->delete();
    }

    protected function dropUsersCrmClientId(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'crm_client_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crm_client_id');
        });
    }

    protected function forgetCrmActivity(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        DB::table('activity_log')
            ->whereIn('subject_type', $this->crmActivitySubjects)
            ->delete();
    }

    protected function forgetCrmPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->crmPermissionNames)
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
};
