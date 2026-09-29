<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_assessment_questions', function (Blueprint $table) {
            $table->id();
            // Null for Question Bank items. A version holds its own copies, so
            // editing the bank never changes a quiz.
            $table->foreignId('assessment_version_id')->nullable()->constrained('ro_assessment_versions')->cascadeOnDelete();
            // The bank question a version copy came from, for reference only.
            $table->foreignId('bank_question_id')->nullable()->constrained('ro_assessment_questions')->nullOnDelete();
            $table->string('type', 30);
            $table->text('prompt');
            // sha1 of the trimmed, lower-cased prompt; used to spot duplicates.
            $table->char('prompt_hash', 40);
            $table->unsignedSmallInteger('points')->default(1);
            $table->text('explanation')->nullable();
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->json('metadata')->nullable();
            $table->string('source', 20)->default('manual');
            $table->uuid('import_batch')->nullable();
            $table->unsignedInteger('import_row')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assessment_version_id', 'sort_order']);
            $table->index(['assessment_version_id', 'prompt_hash']);
            $table->index(['type', 'category']);
            $table->index('import_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_assessment_questions');
    }
};
