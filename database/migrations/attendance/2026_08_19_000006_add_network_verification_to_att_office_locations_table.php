<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('att_office_locations', function (Blueprint $table) {
            $table->boolean('network_verification_enabled')->default(false)->after('allowed_gps_radius_meters');
            $table->json('authorized_public_ips')->nullable()->after('network_verification_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('att_office_locations', function (Blueprint $table) {
            $table->dropColumn(['network_verification_enabled', 'authorized_public_ips']);
        });
    }
};
