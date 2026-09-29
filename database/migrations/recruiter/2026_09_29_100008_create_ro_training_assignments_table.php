<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_training_assignments', function (Blueprint $table) {
            $table->id();
            // Pinned to the version assigned; a version with learners cannot be removed.
            $table->foreignId('course_version_id')->constrained('ro_training_course_versions')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            // Written by TrainingProgressService only. "overdue" is derived from due_at.
            $table->string('status', 20)->default('assigned');
            $table->timestamp('assigned_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['course_version_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_training_assignments');
    }
};
