<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('att_attendance_entries', function (Blueprint $table) {
            $table->unsignedInteger('total_break_seconds')->default(0)->after('total_working_seconds');
            $table->unsignedInteger('net_working_seconds')->nullable()->after('total_break_seconds');
        });

        Schema::create('att_attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_entry_id')->constrained('att_attendance_entries')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['attendance_entry_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('att_attendance_breaks');

        Schema::table('att_attendance_entries', function (Blueprint $table) {
            $table->dropColumn(['total_break_seconds', 'net_working_seconds']);
        });
    }
};
