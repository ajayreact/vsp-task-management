<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_number', 64)->unique();
            $table->string('title');
            $table->string('contract_type', 64);
            $table->string('country', 32);
            $table->string('currency', 8);
            $table->string('status', 32)->default('draft');
            $table->foreignId('tm_company_id')->constrained('tm_companies')->cascadeOnDelete();
            $table->date('effective_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('current_version_id')->nullable();
            $table->foreignId('original_document_id')->nullable()->constrained('tm_company_documents')->nullOnDelete();
            $table->foreignId('signed_document_id')->nullable()->constrained('tm_company_documents')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'effective_date']);
            $table->index(['tm_company_id', 'status']);
        });

        Schema::create('tm_contract_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tm_contract_id')->constrained('tm_contracts')->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->string('status', 32)->default('active');
            $table->json('snapshot');
            $table->text('change_summary')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['tm_contract_id', 'version_number']);
        });

        Schema::table('tm_contracts', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('tm_contract_versions')->nullOnDelete();
        });

        Schema::create('tm_contract_share_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tm_contract_id')->unique()->constrained('tm_contracts')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('short_code', 10)->nullable()->unique();
            $table->string('expiry_preset', 16)->default('30_days');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('tm_contract_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tm_contract_id')->constrained('tm_contracts')->cascadeOnDelete();
            $table->foreignId('tm_contract_version_id')->constrained('tm_contract_versions')->cascadeOnDelete();
            $table->string('party', 32);
            $table->string('signer_name');
            $table->string('authorized_person')->nullable();
            $table->string('signature_type', 32);
            $table->longText('signature_data');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();
        });

        Schema::create('tm_contract_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tm_contract_id')->constrained('tm_contracts')->cascadeOnDelete();
            $table->string('event', 64);
            $table->string('description');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['tm_contract_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tm_contracts', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('tm_contract_events');
        Schema::dropIfExists('tm_contract_signatures');
        Schema::dropIfExists('tm_contract_share_links');
        Schema::dropIfExists('tm_contract_versions');
        Schema::dropIfExists('tm_contracts');
    }
};
