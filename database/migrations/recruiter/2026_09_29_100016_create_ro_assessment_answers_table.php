<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('ro_assessment_attempts')->cascadeOnDelete();
            // Questions of a published version are never deleted, so answers stay readable.
            $table->foreignId('question_id')->constrained('ro_assessment_questions')->restrictOnDelete();
            // Short-answer response.
            $table->text('text_answer')->nullable();
            // Set by scoring on submit; null until then, and for unreviewed short answers.
            $table->boolean('is_correct')->nullable();
            $table->unsignedSmallInteger('awarded_points')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_feedback')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['needs_review', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_assessment_answers');
    }
};
