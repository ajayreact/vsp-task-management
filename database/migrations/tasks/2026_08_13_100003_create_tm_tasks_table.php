<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_project_id')->constrained('tm_projects')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 20)->default('other');
            $table->string('priority', 20)->default('normal')->index();
            $table->string('status', 20)->default('draft')->index();
            // How the task reaches a person: handed to someone directly, or put
            // on the open board for anyone eligible to claim.
            $table->string('assignment_mode', 10)->default('direct');
            // Null whenever the task is unclaimed, which is the normal state of
            // an open task before someone picks it up.
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // The two lists the module opens with: one person's work queue, and
            // the unclaimed board filtered by department.
            $table->index(['assigned_employee_id', 'status']);
            $table->index(['status', 'assignment_mode', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_tasks');
    }
};
