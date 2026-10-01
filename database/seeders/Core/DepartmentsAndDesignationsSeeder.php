<?php

namespace Database\Seeders\Core;

use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Designation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Default organisational catalogue. Idempotent: a row is skipped when its code
 * or its name (ignoring case and surrounding spaces) already exists, so
 * records an admin created by hand are never duplicated, renamed or removed.
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
            ['code' => 'OPT-RECRUITING', 'name' => 'OPT Recruiting'],
            ['code' => 'BENCH-SALES', 'name' => 'Bench Sales'],
        ] as $department) {
            $this->ensure(Department::class, $department['code'], $department['name']);
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
            ['code' => 'OPT-HEAD', 'name' => 'OPT Head'],
            ['code' => 'SENIOR-OPT-RECRUITER', 'name' => 'Senior OPT Recruiter'],
            ['code' => 'OPT-RECRUITER', 'name' => 'OPT Recruiter'],
            ['code' => 'BENCH-SALES-HEAD', 'name' => 'Bench Sales Head'],
            ['code' => 'SENIOR-BENCH-SALES-RECRUITER', 'name' => 'Senior Bench Sales Recruiter'],
            ['code' => 'BENCH-SALES-RECRUITER', 'name' => 'Bench Sales Recruiter'],
        ] as $designation) {
            $this->ensure(Designation::class, $designation['code'], $designation['name']);
        }
    }

    /**
     * @param  class-string<Department|Designation>  $model
     */
    private function ensure(string $model, string $code, string $name): Model
    {
        $existing = $model::query()
            ->where('code', $code)
            ->orWhereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        return $existing ?? $model::query()->create(['code' => $code, 'name' => $name, 'is_active' => true]);
    }
}
