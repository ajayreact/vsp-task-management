<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->string('channel', 20)->index();
            $table->string('name');
            $table->string('external_account_id')->nullable();
            $table->string('status', 20)->default('connected')->index();
            $table->json('settings')->nullable();
            $table->text('credentials')->nullable();
            $table->string('capture_token', 64)->unique();
            $table->timestamps();

            $table->unique(['crm_client_id', 'channel', 'external_account_id'], 'crm_accounts_client_channel_external_unique');
        });

        Schema::create('crm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('crm_connected_account_id')->nullable()->constrained('crm_connected_accounts')->nullOnDelete();
            $table->string('channel', 20)->index();
            $table->string('name');
            $table->string('external_campaign_id')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('objective', 20)->nullable();
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->decimal('total_budget', 12, 2)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_campaigns');
        Schema::dropIfExists('crm_connected_accounts');
    }
};
