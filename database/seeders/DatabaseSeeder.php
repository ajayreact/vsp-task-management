<?php

namespace Database\Seeders;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $department = Department::firstOrCreate(
            ['code' => 'OPS'],
            ['name' => 'Operations', 'is_active' => true],
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@vsp.test'],
            User::factory()->raw(['name' => 'Local Admin', 'email' => 'admin@vsp.test']),
        );

        $admin->syncRoles(SystemRole::SuperAdmin->value);

        Employee::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'department_id' => $department->id,
                'employee_code' => 'EMP-0001',
                'designation' => 'Administrator',
                'status' => 'active',
                'joined_on' => now()->subYear(),
            ],
        );
    }
}
