<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_training_lesson_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('ro_training_assignments')->cascadeOnDelete();
            // Lessons of a published version are never deleted, so history stays intact.
            $table->foreignId('lesson_id')->constrained('ro_training_lessons')->restrictOnDelete();
            $table->timestamp('started_at')->nullable();
            // Set only by the explicit "Mark Lesson Complete" action, never by audio.
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->unsignedInteger('audio_progress_seconds')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'lesson_id']);
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_training_lesson_completions');
    }
};
