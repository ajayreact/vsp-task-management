<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_training_lesson_contents', function (Blueprint $table) {
            $table->id();
            // Lessons are only deleted inside a draft version, so this never
            // removes content a recruiter has been assigned.
            $table->foreignId('lesson_id')->constrained('ro_training_lessons')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->json('sections');
            $table->string('review_status', 20)->default('needs_review');
            // For a translation: fingerprint of the English sections it was
            // written from, so a changed English lesson flags it as outdated.
            $table->string('source_fingerprint', 64)->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['lesson_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_training_lesson_contents');
    }
};
