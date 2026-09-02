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
            $table->unsignedBigInteger('tm_content_calendar_item_id')->unique();
            $table->string('token', 64)->unique();
            $table->string('short_code', 10)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('tm_content_calendar_item_id', 'tm_cc_item_share_item_fk')
                ->references('id')
                ->on('tm_content_calendar_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_content_calendar_item_share_links');
    }
};
