<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only status timeline for content calendar items.
 * Kept separate from Spatie activity_log for efficient per-item queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tm_content_calendar_status_histories');

        Schema::create('tm_content_calendar_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tm_content_calendar_item_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('note')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tm_content_calendar_item_id', 'tm_cc_status_hist_item_fk')
                ->references('id')
                ->on('tm_content_calendar_items')
                ->cascadeOnDelete();

            $table->index(['tm_content_calendar_item_id', 'created_at'], 'tm_cc_status_hist_item_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_content_calendar_status_histories');
    }
};
