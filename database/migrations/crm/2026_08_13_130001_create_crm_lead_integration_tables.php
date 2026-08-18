<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_ingest_logs');
        Schema::dropIfExists('crm_lead_field_mappings');
        Schema::dropIfExists('crm_lead_sources');
    }
};
