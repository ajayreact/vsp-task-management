<?php

namespace Database\Seeders;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Enums\UserType;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Designation;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Database\Seeders\Core\DepartmentsAndDesignationsSeeder;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Database\Seeders\TaskManagement\DemoWorkSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(DepartmentsAndDesignationsSeeder::class);

        $department = Department::query()->where('code', 'OPS')->firstOrFail();
        $designation = Designation::query()->where('code', 'OPS-HEAD')->firstOrFail();

        $admin = User::query()
            ->role(SystemRole::SuperAdmin->value)
            ->first()
            ?? User::query()->where('email', 'admin@vsp.test')->first();

        if ($admin === null) {
            $admin = User::query()->create([
                'name' => 'Ajay',
                'email' => 'ajay@vspcrm.in',
                'password' => Hash::make('Ajay@123'),
                'user_type' => UserType::Internal,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        } else {
            $admin->update([
                'name' => 'Ajay',
                'email' => 'ajay@vspcrm.in',
                'password' => Hash::make('Ajay@123'),
                'is_active' => true,
            ]);
        }

        $admin->syncRoles(SystemRole::SuperAdmin->value);

        $employee = Employee::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'employee_code' => 'EMP-0001',
                'status' => 'active',
                'joined_on' => now()->subYear(),
            ],
        );

        // Keep the Operations Head profile aligned if the row already existed.
        $employee->fill([
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ])->save();

        // Sample work to click through. Local only: a real deployment starts
        // with the admin account and nothing else.
        if (app()->environment('local')) {
            $this->call(DemoWorkSeeder::class);
        }
    }
}
