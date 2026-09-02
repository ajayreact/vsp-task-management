<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_company_document_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_company_document_id')->unique()->constrained('tm_company_documents')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('short_code', 10)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_company_document_share_links');
    }
};
