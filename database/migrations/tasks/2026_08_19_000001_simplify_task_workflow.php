<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_deliverables', function (Blueprint $table) {
            $table->text('client_feedback')->nullable()->after('notes');
        });

        DB::table('tm_tasks')
            ->where('status', 'accepted')
            ->update(['status' => 'in_progress']);

        DB::table('tm_tasks')
            ->where('status', 'in_progress')
            ->whereNull('started_at')
            ->update(['started_at' => now()]);

        DB::table('tm_tasks')
            ->where('status', 'approved')
            ->update(['status' => 'in_review']);
    }

    public function down(): void
    {
        Schema::table('tm_deliverables', function (Blueprint $table) {
            $table->dropColumn('client_feedback');
        });
    }
};
