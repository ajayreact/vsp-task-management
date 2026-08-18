<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_task_subtasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tm_task_id', 'sort_order']);
            $table->index(['tm_task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_task_subtasks');
    }
};
