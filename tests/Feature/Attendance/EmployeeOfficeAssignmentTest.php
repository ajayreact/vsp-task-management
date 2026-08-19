<?php

use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\EmployeeStatus;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Designation;
use App\Modules\Core\Models\Employee;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate(SystemRole::Employee->value, 'web');
});

function officeAssignmentPayload(array $overrides = []): array
{
    $department = Department::factory()->create();
    $designation = Designation::factory()->create();

    return array_merge([
        'name' => 'Office Assignee',
        'email' => 'office-assignee@vsp.test',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
        'employee_code' => 'EMP-OFFICE-1',
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

test('super admin can assign an active office when creating an employee', function () {
    $office = OfficeLocation::factory()->create(['name' => 'Main Office']);

    $this->actingAs(superAdmin())
        ->post('/admin/employees', officeAssignmentPayload([
            'office_location_id' => $office->id,
        ]))
        ->assertRedirect('/admin/employees')
        ->assertSessionHas('success');

    $employee = Employee::query()->where('employee_code', 'EMP-OFFICE-1')->sole();

    expect(EmployeeOfficeAssignment::query()->where('employee_id', $employee->id)->value('office_location_id'))
        ->toBe($office->id);
});

test('super admin can change or clear an employee office assignment', function () {
    $employee = Employee::factory()->create();
    $firstOffice = OfficeLocation::factory()->create(['name' => 'First Office']);
    $secondOffice = OfficeLocation::factory()->create(['name' => 'Second Office']);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $firstOffice->id,
    ]);

    $payload = officeAssignmentPayload([
        'name' => $employee->user->name,
        'email' => $employee->user->email,
        'employee_code' => $employee->employee_code,
        'department_id' => $employee->department_id,
        'designation_id' => $employee->designation_id,
        'password' => '',
        'password_confirmation' => '',
        'office_location_id' => $secondOffice->id,
    ]);

    $this->actingAs(superAdmin())
        ->put("/admin/employees/{$employee->id}", $payload)
        ->assertRedirect('/admin/employees');

    expect(EmployeeOfficeAssignment::query()->where('employee_id', $employee->id)->value('office_location_id'))
        ->toBe($secondOffice->id);

    $this->actingAs(superAdmin())
        ->put("/admin/employees/{$employee->id}", array_merge($payload, [
            'office_location_id' => null,
        ]))
        ->assertRedirect('/admin/employees');

    expect(EmployeeOfficeAssignment::query()->where('employee_id', $employee->id)->exists())->toBeFalse();
});

test('super admin cannot assign an inactive office location', function () {
    $office = OfficeLocation::factory()->inactive()->create();

    $this->actingAs(superAdmin())
        ->post('/admin/employees', officeAssignmentPayload([
            'office_location_id' => $office->id,
        ]))
        ->assertSessionHasErrors('office_location_id');

    expect(Employee::query()->where('employee_code', 'EMP-OFFICE-1')->exists())->toBeFalse();
});

test('staff without super admin role cannot assign office locations', function () {
    $employee = Employee::factory()->create();
    $office = OfficeLocation::factory()->create();

    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->post('/admin/employees', officeAssignmentPayload([
            'office_location_id' => $office->id,
        ]))
        ->assertRedirect('/admin/employees');

    $created = Employee::query()->where('employee_code', 'EMP-OFFICE-1')->sole();
    expect(EmployeeOfficeAssignment::query()->where('employee_id', $created->id)->exists())->toBeFalse();

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->actingAs(staffWith(Ability::ManageEmployees))
        ->put("/admin/employees/{$employee->id}", officeAssignmentPayload([
            'name' => $employee->user->name,
            'email' => $employee->user->email,
            'employee_code' => $employee->employee_code,
            'department_id' => $employee->department_id,
            'designation_id' => $employee->designation_id,
            'password' => '',
            'password_confirmation' => '',
            'office_location_id' => OfficeLocation::factory()->create()->id,
        ]))
        ->assertRedirect('/admin/employees');

    expect(EmployeeOfficeAssignment::query()->where('employee_id', $employee->id)->value('office_location_id'))
        ->toBe($office->id);
});

test('employee list includes assigned office for viewers', function () {
    $employee = Employee::factory()->create();
    $office = OfficeLocation::factory()->create(['name' => 'HQ Office']);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get('/admin/employees')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/admin/employees/index')
            ->where('employees.data.0.office_location.name', 'HQ Office'));
});

test('super admin edit form receives active office options', function () {
    $employee = Employee::factory()->create();
    $active = OfficeLocation::factory()->create(['name' => 'Active Office']);
    OfficeLocation::factory()->inactive()->create(['name' => 'Closed Office']);

    $this->actingAs(superAdmin())
        ->get("/admin/employees/{$employee->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/admin/employees/edit')
            ->has('officeLocations', 1)
            ->where('officeLocations.0.id', $active->id));
});
