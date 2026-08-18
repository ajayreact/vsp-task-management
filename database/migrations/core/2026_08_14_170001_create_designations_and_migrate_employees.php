<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // Seed the catalogue inside the migration so the backfill below can
        // resolve codes even before the application seeder has run.
        $now = now();
        $catalogue = [
            ['code' => 'OPS-HEAD', 'name' => 'Operations Head'],
            ['code' => 'TEAM-LEAD', 'name' => 'Team Lead'],
            ['code' => 'GRAPHIC-DESIGNER', 'name' => 'Graphic Designer'],
            ['code' => 'CONTENT-WRITER', 'name' => 'Content Writer'],
            ['code' => 'SEO-SPECIALIST', 'name' => 'SEO Specialist'],
        ];

        foreach ($catalogue as $row) {
            DB::table('designations')->insert([
                'code' => $row['code'],
                'name' => $row['name'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('designation_id')
                ->nullable()
                ->after('department_id')
                ->constrained('designations')
                ->nullOnDelete();
        });

        $byName = DB::table('designations')->pluck('id', 'name');
        $byCode = DB::table('designations')->pluck('id', 'code');

        $legacyMap = [
            'Operations Head' => 'OPS-HEAD',
            'Team Lead' => 'TEAM-LEAD',
            'Creative Lead' => 'TEAM-LEAD',
            'Graphic Designer' => 'GRAPHIC-DESIGNER',
            'Senior Designer' => 'GRAPHIC-DESIGNER',
            'Content Writer' => 'CONTENT-WRITER',
            'Copywriter' => 'CONTENT-WRITER',
            'SEO Specialist' => 'SEO-SPECIALIST',
            'SEO Executive' => 'SEO-SPECIALIST',
        ];

        foreach (DB::table('employees')->whereNotNull('designation')->get(['id', 'designation']) as $employee) {
            $label = trim((string) $employee->designation);
            $id = $byName[$label] ?? null;

            if ($id === null && isset($legacyMap[$label])) {
                $id = $byCode[$legacyMap[$label]] ?? null;
            }

            if ($id === null) {
                $id = $byCode['GRAPHIC-DESIGNER'] ?? null;
            }

            if ($id !== null) {
                DB::table('employees')->where('id', $employee->id)->update(['designation_id' => $id]);
            }
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('designation');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('designation')->nullable()->after('department_id');
        });

        $names = DB::table('designations')->pluck('name', 'id');

        foreach (DB::table('employees')->whereNotNull('designation_id')->get(['id', 'designation_id']) as $employee) {
            DB::table('employees')->where('id', $employee->id)->update([
                'designation' => $names[$employee->designation_id] ?? null,
            ]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('designation_id');
        });

        Schema::dropIfExists('designations');
    }
};
