<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('att_attendance_entries', function (Blueprint $table) {
            $table->string('work_mode', 16)->default('office')->after('status');
            $table->index(['attendance_date', 'work_mode']);
        });
    }

    public function down(): void
    {
        Schema::table('att_attendance_entries', function (Blueprint $table) {
            $table->dropIndex(['attendance_date', 'work_mode']);
            $table->dropColumn('work_mode');
        });
    }
};
