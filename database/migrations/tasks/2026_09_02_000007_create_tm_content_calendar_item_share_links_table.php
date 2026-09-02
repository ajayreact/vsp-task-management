<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_content_calendar_item_share_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tm_content_calendar_item_id');
            $table->string('token', 64);
            $table->string('short_code', 10)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();

            $table->unique('tm_content_calendar_item_id', 'tm_cc_item_share_item_uq');
            $table->unique('token', 'tm_cc_item_share_token_uq');
            $table->unique('short_code', 'tm_cc_item_share_short_uq');

            $table->foreign('tm_content_calendar_item_id', 'tm_cc_item_share_item_fk')
                ->references('id')
                ->on('tm_content_calendar_items')
                ->cascadeOnDelete();

            $table->foreign('created_by_user_id', 'tm_cc_item_share_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_content_calendar_item_share_links');
    }
};
