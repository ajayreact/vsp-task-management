<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_pipeline_id')->constrained('crm_pipelines')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->unsignedTinyInteger('win_probability')->default(0);
            $table->string('type', 10)->default('open')->index();
            $table->timestamps();

            $table->index(['crm_pipeline_id', 'sort_order']);
        });

        Schema::create('crm_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_pipeline_id')->constrained('crm_pipelines')->restrictOnDelete();
            $table->foreignId('crm_pipeline_stage_id')->constrained('crm_pipeline_stages')->restrictOnDelete();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->restrictOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->foreignId('crm_campaign_id')->nullable()->constrained('crm_campaigns')->nullOnDelete();
            $table->foreignId('crm_lead_id')->nullable()->unique()->constrained('crm_leads')->nullOnDelete();
            $table->string('name');
            $table->decimal('value', 12, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->date('expected_close_on')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('lost_reason')->nullable();
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['crm_pipeline_id', 'crm_pipeline_stage_id']);
            $table->index(['crm_client_id', 'closed_at']);
        });

        Schema::create('crm_deal_stage_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_deal_id')->constrained('crm_deals')->cascadeOnDelete();
            $table->foreignId('crm_pipeline_stage_id')->constrained('crm_pipeline_stages')->restrictOnDelete();
            $table->foreignId('from_crm_pipeline_stage_id')->nullable()->constrained('crm_pipeline_stages')->nullOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('lost_reason')->nullable();
            $table->timestamps();

            $table->index(['crm_deal_id', 'entered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_deal_stage_history');
        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('crm_pipeline_stages');
        Schema::dropIfExists('crm_pipelines');
    }
};
