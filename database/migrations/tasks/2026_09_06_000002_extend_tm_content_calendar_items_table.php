<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_content_calendar_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('post_number')->nullable()->after('scheduled_time');
            $table->string('topic', 40)->default('other')->after('content_type');
            $table->text('caption')->nullable()->after('description');
            $table->text('hashtags')->nullable()->after('caption');
            $table->text('client_feedback')->nullable()->after('internal_notes');
            $table->timestamp('reviewed_at')->nullable()->after('client_feedback');
            $table->timestamp('published_at')->nullable()->after('reviewed_at');
            $table->string('published_url', 2048)->nullable()->after('published_at');

            $table->unique(
                ['tm_company_id', 'scheduled_date', 'post_number'],
                'tm_cc_items_company_date_post_unique',
            );
            $table->index('topic');
        });
    }

    public function down(): void
    {
        Schema::table('tm_content_calendar_items', function (Blueprint $table) {
            $table->dropUnique('tm_cc_items_company_date_post_unique');
            $table->dropIndex(['topic']);
            $table->dropColumn([
                'post_number',
                'topic',
                'caption',
                'hashtags',
                'client_feedback',
                'reviewed_at',
                'published_at',
                'published_url',
            ]);
        });
    }
};
