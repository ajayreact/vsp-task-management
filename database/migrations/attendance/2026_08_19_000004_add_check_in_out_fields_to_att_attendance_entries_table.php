<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('att_attendance_entries', function (Blueprint $table) {
            $table->foreignId('office_location_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('att_office_locations')
                ->nullOnDelete();
            $table->timestamp('check_in_at')->nullable()->after('status');
            $table->decimal('check_in_latitude', 10, 7)->nullable()->after('check_in_at');
            $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
            $table->timestamp('check_out_at')->nullable()->after('check_in_longitude');
            $table->decimal('check_out_latitude', 10, 7)->nullable()->after('check_out_at');
            $table->decimal('check_out_longitude', 10, 7)->nullable()->after('check_out_latitude');
            $table->unsignedInteger('total_working_seconds')->nullable()->after('check_out_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('att_attendance_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_location_id');
            $table->dropColumn([
                'check_in_at',
                'check_in_latitude',
                'check_in_longitude',
                'check_out_at',
                'check_out_latitude',
                'check_out_longitude',
                'total_working_seconds',
            ]);
        });
    }
};
