<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('activity_date');
            $table->string('activity_type', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            // Always calculated on the server from start and end time.
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            // Removing a task leaves the activity in place, just unlinked.
            $table->foreignId('recruiter_task_id')->nullable()->constrained('ro_tasks')->nullOnDelete();
            // A number the recruiter reports, never a link to candidate records.
            $table->unsignedInteger('quantity')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'activity_date']);
            $table->index(['activity_date', 'activity_type']);
            $table->index('recruiter_task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_daily_activities');
    }
};
