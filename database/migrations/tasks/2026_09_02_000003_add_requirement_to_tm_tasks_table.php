<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_tasks', function (Blueprint $table) {
            $table->text('requirement')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('tm_tasks', function (Blueprint $table) {
            $table->dropColumn('requirement');
        });
    }
};
