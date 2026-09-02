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
            $table->unsignedBigInteger('tm_company_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('token', 64);
            $table->string('short_code', 10)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();

            $table->unique('token', 'tm_cc_sched_share_token_uq');
            $table->unique('short_code', 'tm_cc_sched_share_short_uq');
            $table->unique(['tm_company_id', 'period_start', 'period_end'], 'tm_cc_sched_share_period_uq');

            $table->foreign('tm_company_id', 'tm_cc_sched_share_company_fk')
                ->references('id')
                ->on('tm_companies')
                ->cascadeOnDelete();

            $table->foreign('created_by_user_id', 'tm_cc_sched_share_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_content_calendar_schedule_share_links');
    }
};
