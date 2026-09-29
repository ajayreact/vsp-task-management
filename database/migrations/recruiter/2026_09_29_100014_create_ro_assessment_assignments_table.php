<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_assessment_assignments', function (Blueprint $table) {
            $table->id();
            // Pinned to the version assigned; a version with assignments cannot be removed.
            $table->foreignId('assessment_version_id')->constrained('ro_assessment_versions')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Set when the quiz came with a training course assignment.
            $table->foreignId('training_assignment_id')->nullable()->constrained('ro_training_assignments')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            // assigned / in_progress / completed. "overdue" is derived from due_at.
            $table->string('status', 20)->default('assigned');
            // passed / failed / pending_review, from the deciding attempt.
            $table->string('result', 20)->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_version_id', 'employee_id'], 'ro_assessment_assignment_unique');
            $table->index(['employee_id', 'status']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_assessment_assignments');
    }
};
