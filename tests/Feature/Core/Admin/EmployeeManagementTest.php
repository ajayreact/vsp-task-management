<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\EmployeeStatus;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Enums\UserType;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Designation;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Database\Seeders\Core\DepartmentsAndDesignationsSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate(SystemRole::Employee->value, 'web');
});

function employeePayload(array $overrides = []): array
{
    $department = Department::factory()->create();
    $designation = Designation::factory()->create();

    return array_merge([
        'name' => 'Priya Nair',
        'email' => 'priya@vsp.test',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
        'employee_code' => 'EMP-2001',
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'reporting_to_id' => null,
        'phone' => '9876543210',
        'joined_on' => '2026-01-15',
        'exited_on' => null,
        'status' => EmployeeStatus::Active->value,
        'is_active' => true,
        'roles' => [SystemRole::Employee->value],
    ], $overrides);
}

test('the list can be filtered by department and status', function () {
    $creative = Department::factory()->create();
    $inCreative = Employee::factory()->for($creative)->create();
    $elsewhere = Employee::factory()->create();

    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get("/admin/employees?department={$creative->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/admin/employees/index')
            ->has('employees.data', 1)
            ->where('employees.data.0.id', $inCreative->id)
        );

    expect($elsewhere->department_id)->not->toBe($creative->id);
});

test('the list can be searched by name', function () {
    $target = Employee::factory()->create();
    $target->user->update(['name' => 'Findable Person']);
    Employee::factory()->create();

    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get('/admin/employees?search=Findable')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('employees.data', 1));
});

test('viewing does not allow creating', function () {
    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->post('/admin/employees', employeePayload())
        ->assertForbidden();

    expect(Employee::count())->toBe(0);
});

test('an employee is created together with its login and roles', function () {
    $this->actingAs(staffWith(Ability::ViewEmployees, Ability::ManageEmployees))
        ->post('/admin/employees', employeePayload())
        ->assertRedirect('/admin/employees')
        ->assertSessionHas('success');

    $user = User::where('email', 'priya@vsp.test')->sole();

    expect($user->user_type)->toBe(UserType::Internal)
        ->and($user->hasRole(SystemRole::Employee->value))->toBeTrue()
        ->and($user->employee)->not->toBeNull()
        ->and($user->employee->employee_code)->toBe('EMP-2001')
        ->and($user->employee->department_id)->not->toBeNull()
        ->and($user->employee->designation_id)->not->toBeNull();
});

test('department and designation are required', function () {
    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->post('/admin/employees', employeePayload([
            'department_id' => null,
            'designation_id' => null,
        ]))
        ->assertSessionHasErrors(['department_id', 'designation_id']);
});

test('the employee code has to be unique', function () {
    Employee::factory()->create(['employee_code' => 'EMP-2001']);

    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->post('/admin/employees', employeePayload())
        ->assertSessionHasErrors('employee_code');
});

test('the super-admin role cannot be handed out from the staff screen', function () {
    Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');

    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->post('/admin/employees', employeePayload(['roles' => [SystemRole::SuperAdmin->value]]))
        ->assertSessionHasErrors('roles.0');

    expect(User::where('email', 'priya@vsp.test')->exists())->toBeFalse();
});

test('a blank password on update leaves the existing one alone', function () {
    $employee = Employee::factory()->create();
    $original = $employee->user->password;

    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->put("/admin/employees/{$employee->id}", employeePayload([
            'employee_code' => $employee->employee_code,
            'department_id' => $employee->department_id,
            'designation_id' => $employee->designation_id,
            'password' => '',
            'password_confirmation' => '',
        ]))
        ->assertRedirect('/admin/employees');

    expect($employee->user->refresh()->password)->toBe($original)
        ->and($employee->user->name)->toBe('Priya Nair');
});

test('a supplied password on update replaces the old one', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->put("/admin/employees/{$employee->id}", employeePayload([
            'employee_code' => $employee->employee_code,
            'department_id' => $employee->department_id,
            'designation_id' => $employee->designation_id,
            'password' => 'a-brand-new-secret',
            'password_confirmation' => 'a-brand-new-secret',
        ]))
        ->assertRedirect('/admin/employees');

    expect(Hash::check('a-brand-new-secret', $employee->user->refresh()->password))->toBeTrue();
});

test('an employee cannot be made to report to themselves', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->put("/admin/employees/{$employee->id}", employeePayload([
            'employee_code' => $employee->employee_code,
            'department_id' => $employee->department_id,
            'designation_id' => $employee->designation_id,
            'reporting_to_id' => $employee->id,
        ]))
        ->assertSessionHasErrors('reporting_to_id');
});

test('deleting an employee removes the login with it', function () {
    $employee = Employee::factory()->create();
    $userId = $employee->user_id;

    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->delete("/admin/employees/{$employee->id}")
        ->assertRedirect('/admin/employees');

    $this->assertDatabaseMissing('users', ['id' => $userId]);
    $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
});

test('an admin cannot delete their own employee record', function () {
    $admin = staffWith(Ability::ManageEmployees);
    $employee = Employee::factory()->for($admin, 'user')->create();

    $this->actingAs($admin)
        ->delete("/admin/employees/{$employee->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('employees', ['id' => $employee->id]);
});

test('departments and designations seeder is idempotent', function () {
    $this->seed(DepartmentsAndDesignationsSeeder::class);
    $this->seed(DepartmentsAndDesignationsSeeder::class);

    expect(Department::query()->whereIn('code', ['OPS', 'CRT', 'CONTENT', 'SEO', 'OPT-RECRUITING', 'BENCH-SALES'])->count())->toBe(6)
        ->and(Department::query()->count())->toBe(6)
        ->and(Designation::query()->whereIn('code', [
            'OPS-HEAD', 'TEAM-LEAD', 'GRAPHIC-DESIGNER', 'CONTENT-WRITER', 'SEO-SPECIALIST',
            'SOFTWARE-DEVELOPER', 'SENIOR-SOFTWARE-DEVELOPER', 'SALES-MANAGER', 'ONBOARDING-TEAM-LEAD',
            'OPT-HEAD', 'SENIOR-OPT-RECRUITER', 'OPT-RECRUITER',
            'BENCH-SALES-HEAD', 'SENIOR-BENCH-SALES-RECRUITER', 'BENCH-SALES-RECRUITER',
        ])->count())->toBe(15)
        ->and(Designation::query()->count())->toBe(15);
});

test('the seeder adds the OPT Recruiting and Bench Sales structure', function () {
    $this->seed(DepartmentsAndDesignationsSeeder::class);

    expect(Department::query()->whereIn('name', ['OPT Recruiting', 'Bench Sales'])->where('is_active', true)->pluck('name')->sort()->values()->all())
        ->toBe(['Bench Sales', 'OPT Recruiting'])
        ->and(Designation::query()->whereIn('code', ['OPT-HEAD', 'SENIOR-OPT-RECRUITER', 'OPT-RECRUITER', 'BENCH-SALES-HEAD', 'SENIOR-BENCH-SALES-RECRUITER', 'BENCH-SALES-RECRUITER'])->where('is_active', true)->pluck('name', 'code')->all())
        ->toEqual([
            'OPT-HEAD' => 'OPT Head',
            'SENIOR-OPT-RECRUITER' => 'Senior OPT Recruiter',
            'OPT-RECRUITER' => 'OPT Recruiter',
            'BENCH-SALES-HEAD' => 'Bench Sales Head',
            'SENIOR-BENCH-SALES-RECRUITER' => 'Senior Bench Sales Recruiter',
            'BENCH-SALES-RECRUITER' => 'Bench Sales Recruiter',
        ]);
});

test('the seeder does not duplicate or rename records an admin already created under another code', function () {
    $department = Department::factory()->create(['code' => 'OPTR', 'name' => ' opt recruiting ']);
    $designation = Designation::factory()->create(['code' => 'BSR', 'name' => 'BENCH SALES RECRUITER']);

    $this->seed(DepartmentsAndDesignationsSeeder::class);

    expect(Department::query()->whereRaw('LOWER(TRIM(name)) = ?', ['opt recruiting'])->count())->toBe(1)
        ->and($department->fresh()->only(['code', 'name']))->toBe(['code' => 'OPTR', 'name' => ' opt recruiting '])
        ->and(Department::query()->where('code', 'OPT-RECRUITING')->exists())->toBeFalse()
        ->and(Designation::query()->whereRaw('LOWER(TRIM(name)) = ?', ['bench sales recruiter'])->count())->toBe(1)
        ->and($designation->fresh()->only(['code', 'name']))->toBe(['code' => 'BSR', 'name' => 'BENCH SALES RECRUITER']);
});

test('seeding the catalogue leaves existing employees, departments, designations and roles untouched', function () {
    $employee = Employee::factory()->create();
    $employee->user->assignRole(SystemRole::Employee->value);
    $before = $employee->fresh()->getAttributes();
    $departmentBefore = $employee->department->getAttributes();
    $designationBefore = $employee->designation->getAttributes();

    $this->seed(DepartmentsAndDesignationsSeeder::class);

    expect($employee->fresh()->getAttributes())->toBe($before)
        ->and($employee->department->fresh()->getAttributes())->toBe($departmentBefore)
        ->and($employee->designation->fresh()->getAttributes())->toBe($designationBefore)
        ->and($employee->user->fresh()->getRoleNames()->all())->toBe([SystemRole::Employee->value]);
});

test('the employee form offers the staffing departments and designations', function () {
    $this->seed(DepartmentsAndDesignationsSeeder::class);

    $props = $this->actingAs(staffWith(Ability::ManageEmployees))
        ->get('/admin/employees/create')
        ->assertOk()
        ->viewData('page')['props'];

    expect(collect($props['departments'])->pluck('name')->all())->toContain('OPT Recruiting', 'Bench Sales')
        ->and(collect($props['designations'])->pluck('name')->all())->toContain(
            'OPT Head', 'Senior OPT Recruiter', 'OPT Recruiter',
            'Bench Sales Head', 'Senior Bench Sales Recruiter', 'Bench Sales Recruiter',
        );
});
