<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_companies', function (Blueprint $table) {
            $table->unsignedSmallInteger('monthly_post_target')->nullable()->after('notes');
            $table->boolean('holiday_india_enabled')->default(true)->after('monthly_post_target');
            $table->boolean('holiday_usa_enabled')->default(false)->after('holiday_india_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('tm_companies', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_post_target',
                'holiday_india_enabled',
                'holiday_usa_enabled',
            ]);
        });
    }
};
