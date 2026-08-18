<?php

use App\Modules\Core\Enums\Ability;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 3: drop Lead Management schema after the application layer was removed.
 * Does not alter previously executed migrations. Existing crm_deals rows are kept.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    protected array $leadActivitySubjects = [
        'App\\Modules\\Crm\\Models\\Lead',
        'App\\Modules\\Crm\\Models\\LeadAssignment',
        'App\\Modules\\Crm\\Models\\FollowUp',
        'App\\Modules\\Crm\\Models\\AssignmentRule',
        'App\\Modules\\Crm\\Models\\LeadIntegration',
        'App\\Modules\\Crm\\Models\\LeadFieldMapping',
        'App\\Modules\\Crm\\Models\\LeadIngestLog',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('crm_deals', 'crm_lead_id')) {
            Schema::table('crm_deals', function (Blueprint $table) {
                $table->dropForeign(['crm_lead_id']);
            });
            Schema::table('crm_deals', function (Blueprint $table) {
                $table->dropColumn('crm_lead_id');
            });
        }

        Schema::dropIfExists('crm_lead_ingest_logs');
        Schema::dropIfExists('crm_lead_field_mappings');
        Schema::dropIfExists('crm_lead_sources');

        Schema::dropIfExists('crm_follow_ups');
        Schema::dropIfExists('crm_lead_assignments');
        Schema::dropIfExists('crm_leads');

        Schema::dropIfExists('crm_assignment_rules');

        $this->forgetLeadActivity();
        $this->forgetRetiredPermissions();
    }

    public function down(): void
    {
        Schema::create('crm_assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_client_id')->nullable()->constrained('crm_clients')->cascadeOnDelete();
            $table->string('channel', 20)->nullable()->index();
            $table->string('strategy', 30);
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('last_assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('crm_campaign_id')->nullable()->constrained('crm_campaigns')->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->foreignId('crm_connected_account_id')->nullable()->constrained('crm_connected_accounts')->nullOnDelete();
            $table->string('channel', 20)->index();
            $table->string('source', 20)->index();
            $table->string('stage', 20)->default('new')->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('company_name')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['crm_client_id', 'stage']);
        });

        Schema::create('crm_lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 20);
            $table->timestamp('assigned_at');
            $table->timestamps();
        });

        Schema::create('crm_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('notes');
            $table->string('outcome', 30)->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['crm_lead_id', 'due_at']);
        });

        Schema::create('crm_lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_client_id')->nullable()->constrained('crm_clients')->nullOnDelete();
            $table->string('name');
            $table->string('type', 30)->index();
            $table->string('token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_lead_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_lead_source_id')->constrained('crm_lead_sources')->cascadeOnDelete();
            $table->string('incoming_key');
            $table->string('crm_field');
            $table->timestamps();

            $table->unique(['crm_lead_source_id', 'incoming_key'], 'crm_lead_mappings_source_key_unique');
        });

        Schema::create('crm_lead_ingest_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_lead_source_id')->nullable()->constrained('crm_lead_sources')->nullOnDelete();
            $table->string('status', 20)->index();
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->timestamps();

            $table->index(['crm_lead_source_id', 'created_at']);
        });

        if (Schema::hasTable('crm_deals') && ! Schema::hasColumn('crm_deals', 'crm_lead_id')) {
            Schema::table('crm_deals', function (Blueprint $table) {
                $table->foreignId('crm_lead_id')
                    ->nullable()
                    ->unique()
                    ->after('crm_campaign_id')
                    ->constrained('crm_leads')
                    ->nullOnDelete();
            });
        }
    }

    protected function forgetLeadActivity(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        DB::table('activity_log')
            ->whereIn('subject_type', $this->leadActivitySubjects)
            ->delete();
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
};
