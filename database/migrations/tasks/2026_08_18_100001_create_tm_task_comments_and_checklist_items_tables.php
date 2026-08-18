<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['tm_task_id', 'created_at']);
        });

        Schema::create('tm_task_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tm_task_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_task_checklist_items');
        Schema::dropIfExists('tm_task_comments');
    }
};
