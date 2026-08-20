<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\AssignmentAction;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskComment;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();
});

function pendingDirectAssignmentTask(): array
{
    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create();

    test()->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $assignee->id])
        ->assertRedirect();

    return [$manager, $assignee, $task->fresh()];
}

function acceptedDirectAssignmentTask(): array
{
    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create();

    test()->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $assignee->id]);

    test()->actingAs($assignee->user)
        ->post("/tasks/{$task->id}/accept")
        ->assertRedirect();

    return [$manager, $assignee, $task->fresh()];
}

test('super admin viewing a pending assignment assigned to another employee sees no accept decline or claim actions', function () {
    [, $assignee, $task] = pendingDirectAssignmentTask();

    $this->actingAs(superAdmin())
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show')
            ->where('can.can_accept', false)
            ->where('can.can_decline', false)
            ->where('can.can_claim', false)
            ->where('can.can_reassign', true)
            ->where('can.can_move_to_open_board', true));
});

test('assigned employee viewing their own pending task sees accept and decline only', function () {
    [, $assignee, $task] = pendingDirectAssignmentTask();

    $this->actingAs($assignee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show-employee')
            ->where('can.can_accept', true)
            ->where('can.can_decline', true)
            ->where('can.can_claim', false)
            ->where('can.can_reassign', false)
            ->where('can.can_move_to_open_board', false));
});

test('another employee viewing someone else pending task sees no accept decline or claim actions', function () {
    [, $assignee, $task] = pendingDirectAssignmentTask();
    $bystander = employeeWith(Ability::AccessTasks);

    $this->actingAs($bystander->user)
        ->get("/tasks/{$task->id}")
        ->assertForbidden();
});

test('super admin viewing an accepted assignment sees no claim action', function () {
    [, $assignee, $task] = acceptedDirectAssignmentTask();

    $this->actingAs(superAdmin())
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show')
            ->where('can.can_claim', false)
            ->where('can.can_accept', false)
            ->where('can.can_decline', false)
            ->where('can.can_reassign', false)
            ->where('can.can_move_to_open_board', false));
});

test('assigned employee viewing their accepted task sees no claim action', function () {
    [, $assignee, $task] = acceptedDirectAssignmentTask();

    $this->actingAs($assignee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show-employee')
            ->where('can.can_claim', false)
            ->where('can.can_accept', false)
            ->where('can.can_decline', false));
});

test('eligible employee viewing an open board task can claim it', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->open()->create();

    $this->actingAs($employee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show-employee')
            ->where('can.can_claim', true)
            ->where('can.can_accept', false)
            ->where('can.can_decline', false));
});

test('a directly assigned pending task cannot be claimed by another employee', function () {
    [, $assignee, $task] = pendingDirectAssignmentTask();
    $other = employeeWith(Ability::AccessTasks);

    expect($task->assigned_employee_id)->toBe($assignee->id)
        ->and($task->status)->toBe(TaskStatus::Assigned);

    $this->actingAs($other->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertForbidden();
});

test('an accepted task cannot be claimed', function () {
    [, $assignee, $task] = acceptedDirectAssignmentTask();
    $other = employeeWith(Ability::AccessTasks);

    $this->actingAs($other->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($task->fresh()->assigned_employee_id)->toBe($assignee->id);
});

test('GET task show works repeatedly without server errors', function () {
    [, $assignee, $task] = acceptedDirectAssignmentTask();

    TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $assignee->user->id,
        'body' => 'Discussion comment for SSR payload coverage',
    ]);

    foreach (range(1, 3) as $attempt) {
        $this->actingAs(superAdmin())
            ->get("/tasks/{$task->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('TaskManagement/tasks/show'));
    }
});

test('notification feed route exists and notifications poll route is not referenced', function () {
    expect(Route::has('notifications.feed'))->toBeTrue()
        ->and(Route::has('notifications.poll'))->toBeFalse();
});

test('super admin cannot accept a pending offer on someone else behalf after gate fix', function () {
    [, $assignee, $task] = pendingDirectAssignmentTask();

    $this->actingAs(superAdmin())
        ->post("/tasks/{$task->id}/accept")
        ->assertForbidden();
});

test('accepted assignment keeps direct assignment fields and is not claimable', function () {
    [, $assignee, $task] = acceptedDirectAssignmentTask();

    expect($task->assigned_employee_id)->toBe($assignee->id)
        ->and($task->status)->toBe(TaskStatus::InProgress)
        ->and($task->assignments()->where('status', AssignmentStatus::Accepted)->exists())->toBeTrue();

    $resolver = app(\App\Modules\TaskManagement\Services\TaskActionResolver::class);

    expect($resolver->canClaim($task, superAdmin()))->toBeFalse()
        ->and($resolver->canClaim($task, $assignee->user))->toBeFalse();
});

test('task action resolver exposes reassign and move to open board independently', function () {
    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $openTask = Task::factory()->open()->create();
    $draftTask = Task::factory()->create(['status' => TaskStatus::Draft]);

    $resolver = app(\App\Modules\TaskManagement\Services\TaskActionResolver::class);

    $openActions = $resolver->resolve($openTask, $manager->user);
    $draftActions = $resolver->resolve($draftTask, $manager->user);

    expect($openActions['can_reassign'])->toBeTrue()
        ->and($openActions['can_move_to_open_board'])->toBeFalse()
        ->and($draftActions['can_reassign'])->toBeTrue()
        ->and($draftActions['can_move_to_open_board'])->toBeTrue();
});

test('manager viewing an open board task sees reassign without move to open board', function () {
    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $task = Task::factory()->open()->create();

    $this->actingAs($manager->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show')
            ->where('can.can_reassign', true)
            ->where('can.can_move_to_open_board', false));
});

test('manager viewing a draft task sees both reassign and move to open board', function () {
    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $task = Task::factory()->create(['status' => TaskStatus::Draft]);

    $this->actingAs($manager->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show')
            ->where('can.can_reassign', true)
            ->where('can.can_move_to_open_board', true));
});
