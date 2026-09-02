<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_content_calendar_schedule_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_company_id')->constrained('tm_companies')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('token', 64)->unique();
            $table->string('short_code', 10)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tm_company_id', 'period_start', 'period_end'], 'tm_cc_schedule_share_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_content_calendar_schedule_share_links');
    }
};
