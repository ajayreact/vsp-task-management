<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;

test('an active client can have a primary responsible employee and supporting employees', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);
    $primary = employeeWith(Ability::AccessTasks);
    $supportA = employeeWith(Ability::AccessTasks);
    $supportB = employeeWith(Ability::AccessTasks);

    $this->actingAs($manager->user)
        ->post('/tasks/clients', [
            'name' => 'VSP AI & Robotics',
            'code' => 'VSP-AI',
            'status' => 'active',
            'primary_responsible_employee_id' => $primary->id,
            'supporting_employee_ids' => [$supportA->id, $supportB->id],
            'monthly_post_target' => 18,
            'holiday_india_enabled' => true,
            'holiday_usa_enabled' => false,
        ])
        ->assertRedirect();

    $client = Company::query()->where('code', 'VSP-AI')->sole();

    expect($client->primary_responsible_employee_id)->toBe($primary->id)
        ->and($client->supportingEmployees()->pluck('employees.id')->sort()->values()->all())
        ->toBe(collect([$supportA->id, $supportB->id])->sort()->values()->all());
});

test('one employee can be primary or supporting across multiple clients', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);
    $santosh = employeeWith(Ability::AccessTasks);
    $pavan = employeeWith(Ability::AccessTasks);

    $this->actingAs($manager->user)->post('/tasks/clients', [
        'name' => 'VSP AI & Robotics',
        'code' => 'AI-01',
        'status' => 'active',
        'primary_responsible_employee_id' => $santosh->id,
        'supporting_employee_ids' => [$pavan->id],
    ])->assertRedirect();

    $this->actingAs($manager->user)->post('/tasks/clients', [
        'name' => 'VSP Law Associates',
        'code' => 'LAW-01',
        'status' => 'active',
        'primary_responsible_employee_id' => $santosh->id,
        'supporting_employee_ids' => [],
    ])->assertRedirect();

    $this->actingAs($manager->user)->post('/tasks/clients', [
        'name' => 'VSP Techverse',
        'code' => 'TECH-01',
        'status' => 'active',
        'primary_responsible_employee_id' => $pavan->id,
        'supporting_employee_ids' => [$santosh->id],
    ])->assertRedirect();

    expect(Company::query()->where('primary_responsible_employee_id', $santosh->id)->count())->toBe(2)
        ->and(Company::query()->whereHas('supportingEmployees', fn ($q) => $q->where('employees.id', $santosh->id))->count())->toBe(1);
});

test('primary responsible cannot also be selected as supporting', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);
    $primary = employeeWith(Ability::AccessTasks);

    $this->actingAs($manager->user)
        ->post('/tasks/clients', [
            'name' => 'Overlap Client',
            'code' => 'OV-01',
            'status' => 'active',
            'primary_responsible_employee_id' => $primary->id,
            'supporting_employee_ids' => [$primary->id],
        ])
        ->assertSessionHasErrors('supporting_employee_ids');

    expect(Company::query()->where('code', 'OV-01')->exists())->toBeFalse();
});

test('active clients require a primary responsible employee', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);

    $this->actingAs($manager->user)
        ->post('/tasks/clients', [
            'name' => 'No Owner',
            'code' => 'NO-01',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('primary_responsible_employee_id');
});

test('inactive clients can be saved without a primary responsible employee', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);

    $this->actingAs($manager->user)
        ->post('/tasks/clients', [
            'name' => 'Paused Client',
            'code' => 'PA-01',
            'status' => 'inactive',
        ])
        ->assertRedirect();

    expect(Company::query()->where('code', 'PA-01')->sole()->primary_responsible_employee_id)->toBeNull();
});

test('operations can change primary and supporting employees', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);
    $santosh = employeeWith(Ability::AccessTasks);
    $pavan = employeeWith(Ability::AccessTasks);
    $arun = employeeWith(Ability::AccessTasks);

    $client = Company::factory()->create([
        'primary_responsible_employee_id' => $santosh->id,
    ]);
    $client->supportingEmployees()->sync([$pavan->id]);

    $this->actingAs($manager->user)
        ->put("/tasks/clients/{$client->id}", [
            'name' => $client->name,
            'code' => $client->code,
            'status' => 'active',
            'primary_responsible_employee_id' => $pavan->id,
            'supporting_employee_ids' => [$santosh->id, $arun->id],
            'monthly_post_target' => $client->monthly_post_target,
            'holiday_india_enabled' => true,
            'holiday_usa_enabled' => false,
        ])
        ->assertRedirect();

    $client->refresh();

    expect($client->primary_responsible_employee_id)->toBe($pavan->id)
        ->and($client->supportingEmployees()->pluck('employees.id')->sort()->values()->all())
        ->toBe(collect([$santosh->id, $arun->id])->sort()->values()->all());
});

test('unauthorized employees cannot change client responsibility', function () {
    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewCompanies);
    $primary = employeeWith(Ability::AccessTasks);
    $client = Company::factory()->create();

    $this->actingAs($viewer->user)
        ->put("/tasks/clients/{$client->id}", [
            'name' => $client->name,
            'code' => $client->code,
            'status' => 'active',
            'primary_responsible_employee_id' => $primary->id,
        ])
        ->assertForbidden();
});

test('client list displays the primary responsible employee', function () {
    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewCompanies);
    $primary = employeeWith(Ability::AccessTasks);
    $primary->user->forceFill(['name' => 'Santosh Nayak'])->save();

    Company::factory()->create([
        'name' => 'VSP AI & Robotics',
        'primary_responsible_employee_id' => $primary->id,
    ]);

    $this->actingAs($viewer->user)
        ->get('/tasks/clients')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/clients/index')
            ->where('clients.data.0.name', 'VSP AI & Robotics')
            ->where('clients.data.0.primary_responsible.name', 'Santosh Nayak')
            ->has('employees'));
});

test('existing clients without a responsible employee continue to list', function () {
    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewCompanies);
    Company::factory()->create([
        'name' => 'Legacy Client',
        'primary_responsible_employee_id' => null,
    ]);

    $this->actingAs($viewer->user)
        ->get('/tasks/clients')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clients.data.0.name', 'Legacy Client')
            ->where('clients.data.0.primary_responsible', null));
});

test('supporting employees can be removed on edit', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies);
    $primary = employeeWith(Ability::AccessTasks);
    $support = employeeWith(Ability::AccessTasks);

    $client = Company::factory()->create([
        'primary_responsible_employee_id' => $primary->id,
    ]);
    $client->supportingEmployees()->sync([$support->id]);

    $this->actingAs($manager->user)
        ->put("/tasks/clients/{$client->id}", [
            'name' => $client->name,
            'code' => $client->code,
            'status' => 'active',
            'primary_responsible_employee_id' => $primary->id,
            'supporting_employee_ids' => [],
            'monthly_post_target' => $client->monthly_post_target,
            'holiday_india_enabled' => true,
            'holiday_usa_enabled' => false,
        ])
        ->assertRedirect();

    expect($client->fresh()->supportingEmployees)->toHaveCount(0);
});

test('responsible employee filter returns primary and supporting matches', function () {
    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewCompanies);
    $santosh = employeeWith(Ability::AccessTasks);
    $pavan = employeeWith(Ability::AccessTasks);

    Company::factory()->create([
        'name' => 'Primary Match',
        'primary_responsible_employee_id' => $santosh->id,
    ]);
    $asSupport = Company::factory()->create([
        'name' => 'Support Match',
        'primary_responsible_employee_id' => $pavan->id,
    ]);
    $asSupport->supportingEmployees()->sync([$santosh->id]);
    Company::factory()->create([
        'name' => 'Other Client',
        'primary_responsible_employee_id' => $pavan->id,
    ]);

    $this->actingAs($viewer->user)
        ->get('/tasks/clients?responsible='.$santosh->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 2)
            ->where('filters.responsible', $santosh->id)
            ->where('clients.data', fn ($rows) => collect($rows)->pluck('name')->sort()->values()->all() === ['Primary Match', 'Support Match']));
});

test('projects and task assignment remain independent from client responsibility', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewCompanies, Ability::ManageCompanies, Ability::ViewProjects, Ability::ManageProjects, Ability::ManageTasks, Ability::AssignTasks);
    $santosh = employeeWith(Ability::AccessTasks);
    $pavan = employeeWith(Ability::AccessTasks);

    $client = Company::factory()->create([
        'primary_responsible_employee_id' => $santosh->id,
    ]);
    $client->supportingEmployees()->sync([$pavan->id]);

    $project = Project::factory()->create(['tm_company_id' => $client->id]);
    $task = Task::factory()->create([
        'tm_project_id' => $project->id,
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $pavan->id,
        'created_by_user_id' => $manager->user->id,
    ]);

    expect($client->fresh()->primary_responsible_employee_id)->toBe($santosh->id)
        ->and($task->fresh()->assigned_employee_id)->toBe($pavan->id)
        ->and($task->assigned_employee_id)->not->toBe($client->primary_responsible_employee_id);
});
