<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\ProjectStatus;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;

/*
|--------------------------------------------------------------------------
| Work companies and projects
|--------------------------------------------------------------------------
*/

test('companies are listed with their project counts', function () {
    $company = Company::factory()->create(['name' => 'Northwind']);
    Project::factory()->count(2)->create(['tm_company_id' => $company->id]);

    $this->actingAs(staffWith(Ability::AccessTasks, Ability::ViewCompanies))
        ->get('/tasks/companies')
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/companies/index')
            ->where('companies.0.name', 'Northwind')
            ->where('companies.0.projects_count', 2)
            ->where('can.manage', false));
});

test('viewing does not imply managing', function () {
    $this->actingAs(staffWith(Ability::AccessTasks, Ability::ViewCompanies))
        ->post('/tasks/companies', ['name' => 'Northwind', 'code' => 'NW', 'status' => 'active'])
        ->assertForbidden();
});

test('a company code is stored uppercase and must be unique', function () {
    $user = staffWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);

    $this->actingAs($user)->post('/tasks/companies', [
        'name' => 'Northwind',
        'code' => 'nw-01',
        'status' => 'active',
    ]);

    expect(Company::query()->sole()->code)->toBe('NW-01');

    $this->actingAs($user)
        ->post('/tasks/companies', ['name' => 'Other', 'code' => 'NW-01', 'status' => 'active'])
        ->assertSessionHasErrors('code');
});

test('a company with projects cannot be deleted', function () {
    $user = staffWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);
    $company = Company::factory()->create();
    Project::factory()->create(['tm_company_id' => $company->id]);

    $this->actingAs($user)
        ->delete("/tasks/companies/{$company->id}")
        ->assertForbidden();

    expect(Company::query()->count())->toBe(1);
});

test('a project is created with its team in one go', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewProjects, Ability::ManageProjects);
    $member = employeeWith(Ability::AccessTasks);
    $company = Company::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks/projects', [
            'tm_company_id' => $company->id,
            'name' => 'Spring campaign',
            'code' => 'spring-26',
            'status' => 'active',
            'manager_employee_id' => $manager->id,
            'members' => [
                ['employee_id' => $member->id, 'project_role' => 'member'],
                ['employee_id' => $manager->id, 'project_role' => 'lead'],
            ],
        ])
        ->assertRedirect();

    $project = Project::query()->sole();

    expect($project->code)->toBe('SPRING-26')
        ->and($project->status)->toBe(ProjectStatus::Active)
        ->and($project->members)->toHaveCount(2)
        ->and($project->manager_employee_id)->toBe($manager->id);
});

test('the same person cannot be added to a project twice', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewProjects, Ability::ManageProjects);
    $member = employeeWith(Ability::AccessTasks);
    $company = Company::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks/projects', [
            'tm_company_id' => $company->id,
            'name' => 'Spring campaign',
            'code' => 'SPRING',
            'status' => 'active',
            'members' => [
                ['employee_id' => $member->id, 'project_role' => 'member'],
                ['employee_id' => $member->id, 'project_role' => 'lead'],
            ],
        ])
        ->assertSessionHasErrors('members.1.employee_id');
});

test('a due date before the start date is rejected', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewProjects, Ability::ManageProjects);
    $company = Company::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks/projects', [
            'tm_company_id' => $company->id,
            'name' => 'Backwards',
            'code' => 'BACK',
            'status' => 'planning',
            'start_date' => '2026-06-01',
            'due_date' => '2026-05-01',
        ])
        ->assertSessionHasErrors('due_date');
});

test('editing a project replaces the team rather than appending to it', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewProjects, Ability::ManageProjects);
    $first = employeeWith(Ability::AccessTasks);
    $second = employeeWith(Ability::AccessTasks);
    $project = Project::factory()->create();
    $project->members()->attach($first->id, ['project_role' => 'member']);

    $this->actingAs($manager->user)->put("/tasks/projects/{$project->id}", [
        'tm_company_id' => $project->tm_company_id,
        'name' => $project->name,
        'code' => $project->code,
        'status' => 'active',
        'members' => [['employee_id' => $second->id, 'project_role' => 'reviewer']],
    ]);

    $members = $project->refresh()->members;

    expect($members)->toHaveCount(1)
        ->and($members->first()->id)->toBe($second->id);
});

test('a project with tasks cannot be deleted', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewProjects, Ability::ManageProjects);
    $project = Project::factory()->create();
    Task::factory()->create(['tm_project_id' => $project->id]);

    $this->actingAs($manager->user)
        ->delete("/tasks/projects/{$project->id}")
        ->assertForbidden();
});

test('the project page breaks tasks down by status', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewProjects);
    $project = Project::factory()->create();
    Task::factory()->count(2)->create(['tm_project_id' => $project->id]);
    Task::factory()->open()->create(['tm_project_id' => $project->id]);

    $this->actingAs($manager->user)
        ->get("/tasks/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/projects/show')
            ->where('taskCounts.draft', 2)
            ->where('taskCounts.open', 1)
            ->where('can.manage', false));
});
