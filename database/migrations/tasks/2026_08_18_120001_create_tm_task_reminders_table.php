<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_task_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('remind_at');
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['remind_at', 'sent_at']);
            $table->index(['tm_task_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_task_reminders');
    }
};
