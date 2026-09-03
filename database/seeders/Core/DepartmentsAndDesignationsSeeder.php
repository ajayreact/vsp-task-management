<?php

namespace Database\Seeders\Core;

use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Designation;
use Illuminate\Database\Seeder;

/**
 * Default organisational catalogue. Idempotent on stable codes.
 */
class DepartmentsAndDesignationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'OPS', 'name' => 'Operations'],
            ['code' => 'CRT', 'name' => 'Creative'],
            ['code' => 'CONTENT', 'name' => 'Content Creation'],
            ['code' => 'SEO', 'name' => 'SEO'],
        ] as $department) {
            Department::query()->firstOrCreate(
                ['code' => $department['code']],
                ['name' => $department['name'], 'is_active' => true],
            );
        }

        foreach ([
            ['code' => 'OPS-HEAD', 'name' => 'Operations Head'],
            ['code' => 'TEAM-LEAD', 'name' => 'Team Lead'],
            ['code' => 'GRAPHIC-DESIGNER', 'name' => 'Graphic Designer'],
            ['code' => 'CONTENT-WRITER', 'name' => 'Content Writer'],
            ['code' => 'SEO-SPECIALIST', 'name' => 'SEO Specialist'],
            ['code' => 'SOFTWARE-DEVELOPER', 'name' => 'Software Developer'],
            ['code' => 'SENIOR-SOFTWARE-DEVELOPER', 'name' => 'Senior Software Developer'],
            ['code' => 'SALES-MANAGER', 'name' => 'Sales Manager'],
            ['code' => 'ONBOARDING-TEAM-LEAD', 'name' => 'Onboarding Team Lead'],
        ] as $designation) {
            Designation::query()->firstOrCreate(
                ['code' => $designation['code']],
                ['name' => $designation['name'], 'is_active' => true],
            );
        }
    }
}
