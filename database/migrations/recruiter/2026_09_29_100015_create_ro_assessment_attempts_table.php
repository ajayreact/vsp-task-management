<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_assessment_attempts', function (Blueprint $table) {
            $table->id();
            // Assignments with attempts are never withdrawn (AssessmentAssignmentService);
            // the cascade only follows an employee being removed.
            $table->foreignId('assignment_id')->constrained('ro_assessment_assignments')->cascadeOnDelete();
            // The exact version taken, kept on the attempt itself for history.
            $table->foreignId('assessment_version_id')->constrained('ro_assessment_versions')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number');
            // in_progress / submitted / abandoned
            $table->string('status', 20)->default('in_progress');
            // Equals assignment_id while in progress, null otherwise: the unique
            // index allows only one active attempt per assignment.
            $table->unsignedBigInteger('active_assignment_id')->nullable()->unique();
            // Question and option order shown in this attempt.
            $table->json('layout');
            $table->timestamp('started_at');
            // Server deadline when the version has a time limit.
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('auto_submitted')->default(false);
            $table->unsignedInteger('total_points')->default(0);
            $table->unsignedInteger('awarded_points')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            // passed / failed / pending_review
            $table->string('result', 20)->nullable();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'attempt_number']);
            $table->index(['employee_id', 'status']);
            $table->index(['assessment_version_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_assessment_attempts');
    }
};
