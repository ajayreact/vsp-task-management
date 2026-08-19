<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('att_office_locations', function (Blueprint $table) {
            $table->time('late_check_in_time')->default('09:30:00')->after('allowed_gps_radius_meters');
        });
    }

    public function down(): void
    {
        Schema::table('att_office_locations', function (Blueprint $table) {
            $table->dropColumn('late_check_in_time');
        });
    }
};
