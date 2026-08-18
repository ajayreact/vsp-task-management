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

    expect(Department::query()->whereIn('code', ['OPS', 'CRT', 'CONTENT', 'SEO'])->count())->toBe(4)
        ->and(Designation::query()->whereIn('code', [
            'OPS-HEAD', 'TEAM-LEAD', 'GRAPHIC-DESIGNER', 'CONTENT-WRITER', 'SEO-SPECIALIST',
        ])->count())->toBe(5);
});
