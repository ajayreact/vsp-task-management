<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The permanent timeline of every assignment and status change on a
        // recruiter task. Written only by RecruiterTaskWorkflow and
        // RecruiterTaskService, never by activity_log.
        Schema::create('ro_task_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ro_task_id')->constrained('ro_tasks')->cascadeOnDelete();
            $table->string('event', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->foreignId('from_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('to_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['ro_task_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_task_events');
    }
};
