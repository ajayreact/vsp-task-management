<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\DeliverableShareLink;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\DeliverableShareLinkService;
use Illuminate\Database\QueryException;

test('the service creates a share link with a 64-character hex token and short code', function () {
    $deliverable = Deliverable::factory()->create();
    $user = User::factory()->create();

    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, $user);

    expect($link)->toBeInstanceOf(DeliverableShareLink::class)
        ->and($link->tm_deliverable_id)->toBe($deliverable->id)
        ->and($link->created_by_user_id)->toBe($user->id)
        ->and($link->token)->toHaveLength(64)
        ->and($link->token)->toMatch('/^[0-9a-f]{64}$/')
        ->and($link->short_code)->toHaveLength(8)
        ->and($link->short_code)->toMatch('/^[A-Za-z0-9]{8}$/');

    $this->assertDatabaseCount('tm_deliverable_share_links', 1);
    $this->assertDatabaseHas('tm_deliverable_share_links', [
        'id' => $link->id,
        'tm_deliverable_id' => $deliverable->id,
        'token' => $link->token,
        'short_code' => $link->short_code,
        'created_by_user_id' => $user->id,
    ]);
});

test('calling the service twice for the same deliverable returns the same link', function () {
    $deliverable = Deliverable::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $service = app(DeliverableShareLinkService::class);

    $first = $service->getOrCreate($deliverable, $firstUser);
    $second = $service->getOrCreate($deliverable, $secondUser);

    expect($second->id)->toBe($first->id)
        ->and($second->token)->toBe($first->token)
        ->and($second->short_code)->toBe($first->short_code)
        ->and($second->created_by_user_id)->toBe($firstUser->id);

    expect(DeliverableShareLink::query()->where('tm_deliverable_id', $deliverable->id)->count())->toBe(1);
});

test('share links for different deliverables receive unique tokens', function () {
    $user = User::factory()->create();
    $service = app(DeliverableShareLinkService::class);

    $first = $service->getOrCreate(Deliverable::factory()->create(), $user);
    $second = $service->getOrCreate(Deliverable::factory()->create(), $user);

    expect($first->token)->not->toBe($second->token)
        ->and($first->short_code)->not->toBe($second->short_code);

    $this->assertDatabaseCount('tm_deliverable_share_links', 2);
});

test('the database rejects a duplicate short code', function () {
    $user = User::factory()->create();
    $existing = app(DeliverableShareLinkService::class)
        ->getOrCreate(Deliverable::factory()->create(), $user);

    expect(fn () => DeliverableShareLink::query()->create([
        'tm_deliverable_id' => Deliverable::factory()->create()->id,
        'token' => bin2hex(random_bytes(32)),
        'short_code' => $existing->short_code,
        'created_by_user_id' => $user->id,
    ]))->toThrow(QueryException::class);
});

test('the database rejects a duplicate token', function () {
    $user = User::factory()->create();
    $existing = app(DeliverableShareLinkService::class)
        ->getOrCreate(Deliverable::factory()->create(), $user);

    expect(fn () => DeliverableShareLink::query()->create([
        'tm_deliverable_id' => Deliverable::factory()->create()->id,
        'token' => $existing->token,
        'created_by_user_id' => $user->id,
    ]))->toThrow(QueryException::class);
});

test('an unauthorized employee cannot generate a share link', function () {
    $assignee = employeeWith(Ability::AccessTasks);
    $submitter = employeeWith(Ability::AccessTasks);
    $stranger = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['assigned_employee_id' => $assignee->id]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $submitter->id,
    ]);

    $this->actingAs($stranger->user)
        ->post(route('tasks.deliverables.share-link', $deliverable))
        ->assertForbidden();

    $this->assertDatabaseCount('tm_deliverable_share_links', 0);
});

test('the deliverable submitter cannot generate a share link', function () {
    $assignee = employeeWith(Ability::AccessTasks);
    $submitter = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['assigned_employee_id' => $assignee->id]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $submitter->id,
    ]);

    $this->actingAs($submitter->user)
        ->post(route('tasks.deliverables.share-link', $deliverable))
        ->assertForbidden();

    $this->assertDatabaseCount('tm_deliverable_share_links', 0);
});

test('the current assignee cannot generate a share link', function () {
    $assignee = employeeWith(Ability::AccessTasks);
    $submitter = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['assigned_employee_id' => $assignee->id]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $submitter->id,
    ]);

    $this->actingAs($assignee->user)
        ->post(route('tasks.deliverables.share-link', $deliverable))
        ->assertForbidden();

    $this->assertDatabaseCount('tm_deliverable_share_links', 0);
});

test('a user with tasks.view_all can generate a share link', function () {
    $assignee = employeeWith(Ability::AccessTasks);
    $submitter = employeeWith(Ability::AccessTasks);
    $lead = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->create(['assigned_employee_id' => $assignee->id]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $submitter->id,
    ]);

    $this->actingAs($lead->user)
        ->post(route('tasks.deliverables.share-link', $deliverable))
        ->assertRedirect();

    $this->assertDatabaseCount('tm_deliverable_share_links', 1);
});

test('a super admin can generate a share link', function () {
    $assignee = employeeWith(Ability::AccessTasks);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => Task::factory()->create(['assigned_employee_id' => $assignee->id])->id,
        'submitted_by_employee_id' => $assignee->id,
    ]);

    $this->actingAs(superAdmin())
        ->post(route('tasks.deliverables.share-link', $deliverable))
        ->assertRedirect();

    $this->assertDatabaseCount('tm_deliverable_share_links', 1);
});

test('generating a share link twice returns the same url and does not create duplicate rows', function () {
    $lead = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->create(['assigned_employee_id' => $lead->id]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $lead->id,
    ]);

    $this->actingAs($lead->user)
        ->post(route('tasks.deliverables.share-link', $deliverable))
        ->assertRedirect();

    $first = DeliverableShareLink::query()->where('tm_deliverable_id', $deliverable->id)->sole();

    $this->actingAs($lead->user)
        ->post(route('tasks.deliverables.share-link', $deliverable))
        ->assertRedirect();

    $second = DeliverableShareLink::query()->where('tm_deliverable_id', $deliverable->id)->sole();

    expect($second->id)->toBe($first->id)
        ->and($second->token)->toBe($first->token)
        ->and($second->short_code)->toBe($first->short_code)
        ->and($second->publicUrl())->toBe($first->publicUrl())
        ->and($second->publicUrl())->toMatch('#/d/[A-Za-z0-9]{8}$#');

    $this->assertDatabaseCount('tm_deliverable_share_links', 1);
});
