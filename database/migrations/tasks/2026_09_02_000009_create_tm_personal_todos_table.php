<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_personal_todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('note')->nullable();
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->string('priority', 16)->default('normal');
            $table->string('status', 16)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamp('reminder_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->foreignId('tm_task_id')->nullable()->constrained('tm_tasks')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status', 'due_date']);
            $table->index(['reminder_at', 'reminder_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_personal_todos');
    }
};
