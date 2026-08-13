<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direct assignments, open-task claims and the employee's response all land
 * here. One table rather than three keeps "who was offered this task, and what
 * did they say" answerable with a single query, including for tasks that have
 * bounced between several people.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // Null for a claim: nobody assigned it, the employee took it.
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 10);
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('responded_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->index(['tm_task_id', 'status']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_task_assignments');
    }
};
