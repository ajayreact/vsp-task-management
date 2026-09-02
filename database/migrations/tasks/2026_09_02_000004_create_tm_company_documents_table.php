<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_company_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_company_id')->constrained('tm_companies')->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 30);
            $table->text('description')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tm_company_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_company_documents');
    }
};
