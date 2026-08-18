<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_hours', 6, 2)->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period_start']);
        });

        Schema::create('tm_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_running')->default(false);
            $table->string('source', 10);
            $table->text('note')->nullable();
            $table->boolean('is_billable')->default(true);
            $table->foreignId('tm_timesheet_id')->nullable()->constrained('tm_timesheets')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'started_at']);
            $table->index(['tm_task_id', 'started_at']);

            // MySQL allows many NULLs in a unique column, so this is "at most
            // one running timer per person" without blocking finished entries.
            // Kept as a real column (not generated) because InnoDB refuses a
            // foreign key on a column that a stored generated column reads.
            $table->unsignedBigInteger('running_for_employee_id')->nullable();
            $table->unique('running_for_employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_time_entries');
        Schema::dropIfExists('tm_timesheets');
    }
};
