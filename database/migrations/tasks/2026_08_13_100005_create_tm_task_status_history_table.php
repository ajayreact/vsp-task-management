<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kept separate from the shared activity log: this is queried per task to draw
 * a timeline, whereas `activity_log` is a system-wide audit trail scanned by
 * subject. Mixing the two would make the timeline query scan every model's
 * history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_task_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');

            $table->index(['tm_task_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_task_status_history');
    }
};
