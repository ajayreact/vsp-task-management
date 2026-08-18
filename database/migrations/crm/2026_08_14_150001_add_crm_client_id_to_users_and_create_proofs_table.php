<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('crm_client_id')
                ->nullable()
                ->after('user_type')
                ->constrained('crm_clients')
                ->nullOnDelete();
        });

        Schema::create('crm_campaign_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('crm_campaign_id')->constrained('crm_campaigns')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 30)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['crm_client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_campaign_proofs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crm_client_id');
        });
    }
};
