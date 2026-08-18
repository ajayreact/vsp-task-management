<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;

function departmentPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Creative',
        'code' => 'CREATIVE',
        'description' => 'Design and copy.',
        'parent_id' => null,
        'head_employee_id' => null,
        'is_active' => true,
    ], $overrides);
}

test('the list shows how many people each department holds', function () {
    $department = Department::factory()->create();
    Employee::factory()->count(2)->for($department)->create();

    $this->actingAs(staffWith(Ability::ViewDepartments))
        ->get('/admin/departments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/admin/departments/index')
            ->where('departments.data.0.employees_count', 2)
        );
});

test('viewing does not allow creating', function () {
    $this->actingAs(staffWith(Ability::ViewDepartments))
        ->post('/admin/departments', departmentPayload())
        ->assertForbidden();
});

test('a department is created with an uppercased code', function () {
    $this->actingAs(staffWith(Ability::ManageDepartments))
        ->post('/admin/departments', departmentPayload(['code' => 'creative']))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('departments', ['name' => 'Creative', 'code' => 'CREATIVE']);
});

test('the code has to be unique', function () {
    Department::factory()->create(['code' => 'CREATIVE']);

    $this->actingAs(staffWith(Ability::ManageDepartments))
        ->post('/admin/departments', departmentPayload())
        ->assertSessionHasErrors('code');
});

test('a department cannot be its own parent', function () {
    $department = Department::factory()->create();

    $this->actingAs(staffWith(Ability::ManageDepartments))
        ->put("/admin/departments/{$department->id}", departmentPayload([
            'code' => $department->code,
            'parent_id' => $department->id,
        ]))
        ->assertSessionHasErrors('parent_id');
});

test('an empty department can be deleted', function () {
    $department = Department::factory()->create();

    $this->actingAs(staffWith(Ability::ManageDepartments))
        ->delete("/admin/departments/{$department->id}")
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});

test('a department holding people cannot be deleted', function () {
    $department = Department::factory()->create();
    Employee::factory()->for($department)->create();

    $this->actingAs(staffWith(Ability::ManageDepartments))
        ->delete("/admin/departments/{$department->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('departments', ['id' => $department->id]);
});

test('a department with sub-departments cannot be deleted', function () {
    $parent = Department::factory()->create();
    Department::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs(staffWith(Ability::ManageDepartments))
        ->delete("/admin/departments/{$parent->id}")
        ->assertForbidden();
});
