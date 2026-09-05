<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Default monthly_post_target = 18 for NEW clients only.
 * Existing NULL values are intentionally left unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tm_companies', 'monthly_post_target')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE tm_companies MODIFY monthly_post_target SMALLINT UNSIGNED NULL DEFAULT 18');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tm_companies', 'monthly_post_target')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE tm_companies MODIFY monthly_post_target SMALLINT UNSIGNED NULL DEFAULT NULL');
        }
    }
};
