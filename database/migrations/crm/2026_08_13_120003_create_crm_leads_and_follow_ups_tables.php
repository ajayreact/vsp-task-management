<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_follow_ups');
        Schema::dropIfExists('crm_lead_assignments');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('crm_assignment_rules');
    }
};
