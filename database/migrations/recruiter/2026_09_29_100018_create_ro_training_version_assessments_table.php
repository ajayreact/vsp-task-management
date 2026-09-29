<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quizzes that belong to a training course version. Training only
        // references the assessment version; the quiz itself lives in ro_assessment_*.
        Schema::create('ro_training_version_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_version_id')->constrained('ro_training_course_versions')->cascadeOnDelete();
            $table->foreignId('assessment_version_id')->constrained('ro_assessment_versions')->restrictOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['course_version_id', 'assessment_version_id'], 'ro_training_version_assessment_unique');
            $table->index('assessment_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_training_version_assessments');
    }
};
