<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Models\RecruiterTaskEvent;
use App\Modules\TaskManagement\Models\Task;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function recruiterTaskStaff(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

function recruiterTaskFor(Employee $recruiter, ?User $creator = null): RecruiterTask
{
    return RecruiterTask::factory()
        ->assignedTo($recruiter)
        ->createdBy($creator ?? recruiterTaskStaff(SystemRole::RecruiterLead)->user)
        ->create();
}

/**
 * @return list<string>
 */
function recruiterTaskEvents(RecruiterTask $task): array
{
    return $task->events()->orderBy('id')->pluck('event')->map(fn ($event) => $event->value)->all();
}

/**
 * @return list<string>
 */
function recruiterNotificationEvents(User $user): array
{
    return $user->notifications()->get()->map(fn ($notification) => $notification->data['event'])->sort()->values()->all();
}

function validRecruiterTaskPayload(Employee $recruiter, array $overrides = []): array
{
    return [
        'title' => 'Source 20 STEM OPT data analysts',
        'description' => 'LinkedIn and university job boards.',
        'work_type' => 'candidate_sourcing',
        'priority' => 'high',
        'due_at' => now()->addDays(2)->format('Y-m-d H:i'),
        'target_count' => 20,
        'assigned_employee_id' => $recruiter->id,
        ...$overrides,
    ];
}

// A. Access

test('a recruiter sees their own tasks and nobody else\'s', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $colleague = recruiterTaskStaff(SystemRole::Recruiter);
    $mine = recruiterTaskFor($recruiter);
    $theirs = recruiterTaskFor($colleague);

    $this->actingAs($recruiter->user)
        ->get('/recruiter/tasks')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/tasks/index')
            ->where('pageTitle', 'My Tasks')
            ->where('can.viewTeam', false)
            ->where('recruiters', [])
            ->has('tasks.data', 1)
            ->where('tasks.data.0.id', $mine->id));

    $this->actingAs($recruiter->user)->get("/recruiter/tasks/{$mine->id}")->assertOk();
    $this->actingAs($recruiter->user)->get("/recruiter/tasks/{$theirs->id}")->assertForbidden();
});

test('a recruiter cannot widen their list to the team through filters', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $colleague = recruiterTaskStaff(SystemRole::Recruiter);
    recruiterTaskFor($colleague);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/tasks?scope=all&recruiter={$colleague->id}")
        ->assertInertia(fn ($page) => $page
            ->where('filters.scope', 'mine')
            ->where('filters.recruiter', null)
            ->has('tasks.data', 0));
});

test('a recruiter lead sees every recruiter\'s tasks and can filter by recruiter', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $first = recruiterTaskStaff(SystemRole::Recruiter);
    $second = recruiterTaskStaff(SystemRole::Recruiter);
    recruiterTaskFor($first);
    $secondTask = recruiterTaskFor($second);

    $this->actingAs($lead->user)
        ->get('/recruiter/tasks')
        ->assertInertia(fn ($page) => $page
            ->where('pageTitle', 'Recruiter Tasks')
            ->where('can.viewTeam', true)
            ->where('can.create', true)
            ->has('tasks.data', 2));

    $this->actingAs($lead->user)
        ->get("/recruiter/tasks?recruiter={$second->id}")
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 1)
            ->where('tasks.data.0.id', $secondTask->id));

    $this->actingAs($lead->user)->get("/recruiter/tasks/{$secondTask->id}")->assertOk();
});

test('a recruiter without recruiter.tasks.manage cannot create tasks', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $colleague = recruiterTaskStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)->get('/recruiter/tasks/create')->assertForbidden();
    $this->actingAs($recruiter->user)
        ->post('/recruiter/tasks', validRecruiterTaskPayload($colleague))
        ->assertForbidden();

    expect(RecruiterTask::query()->count())->toBe(0);
});

test('a recruiter lead creates a task that opens on the task page', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);

    $this->actingAs($lead->user)
        ->get('/recruiter/tasks/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/tasks/create')
            ->has('workTypes', 8)
            ->where('recruiters', fn ($recruiters) => collect($recruiters)->pluck('id')->contains($recruiter->id)));

    $response = $this->actingAs($lead->user)->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter));

    $task = RecruiterTask::query()->sole();
    $response->assertRedirect("/recruiter/tasks/{$task->id}");

    expect($task->status)->toBe(RecruiterTaskStatus::Assigned)
        ->and($task->assigned_employee_id)->toBe($recruiter->id)
        ->and($task->created_by_user_id)->toBe($lead->user->id)
        ->and($task->target_count)->toBe(20);
});

test('guests and users without recruiter access cannot reach recruiter tasks', function () {
    $this->get('/recruiter/tasks')->assertRedirect('/login');

    $this->actingAs(staffWith(Ability::AccessTasks, Ability::ViewAllTasks, Ability::ManageTasks))
        ->get('/recruiter/tasks')
        ->assertForbidden();
});

// B. Assignment

test('a manager assigns an active recruiter and the task records creation then assignment', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);

    $this->actingAs($lead->user)->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter))->assertRedirect();

    $task = RecruiterTask::query()->sole();
    $events = $task->events()->orderBy('id')->get();

    expect($events)->toHaveCount(2)
        ->and($events[0]->event)->toBe(RecruiterTaskEventType::Created)
        ->and($events[0]->to_status)->toBe(RecruiterTaskStatus::Assigned)
        ->and($events[1]->event)->toBe(RecruiterTaskEventType::Assigned)
        ->and($events[1]->to_employee_id)->toBe($recruiter->id)
        ->and($events[1]->actor_user_id)->toBe($lead->user->id);
});

test('an employee without recruiter access cannot be assigned or offered', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $marketer = recruiterTaskStaff(SystemRole::Employee);

    $this->actingAs($lead->user)
        ->get('/recruiter/tasks/create')
        ->assertInertia(fn ($page) => $page
            ->where('recruiters', fn ($recruiters) => ! collect($recruiters)->pluck('id')->contains($marketer->id)));

    $this->actingAs($lead->user)
        ->post('/recruiter/tasks', validRecruiterTaskPayload($marketer))
        ->assertSessionHasErrors('assigned_employee_id');

    expect(RecruiterTask::query()->count())->toBe(0);
});

test('an inactive recruiter cannot be assigned', function (string $state) {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);

    if ($state === 'exited employee') {
        $recruiter->forceFill(['status' => 'exited', 'exited_on' => now()->subDay()])->save();
    } else {
        $recruiter->user->forceFill(['is_active' => false])->save();
    }

    $this->actingAs($lead->user)
        ->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter))
        ->assertSessionHasErrors('assigned_employee_id');

    expect(RecruiterTask::query()->count())->toBe(0);
})->with(['exited employee', 'deactivated login']);

test('recruiters are identified by permission, never by department', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $marketer = recruiterTaskStaff(SystemRole::Employee);
    $marketer->forceFill(['department_id' => $recruiter->department_id])->save();

    $this->actingAs($lead->user)
        ->post('/recruiter/tasks', validRecruiterTaskPayload($marketer))
        ->assertSessionHasErrors('assigned_employee_id');

    $this->actingAs($lead->user)
        ->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter))
        ->assertSessionHasNoErrors();
});

test('reassignment moves the task and records a reassigned event', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $first = recruiterTaskStaff(SystemRole::Recruiter);
    $second = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($first, $lead->user);

    $this->actingAs($lead->user)
        ->post("/recruiter/tasks/{$task->id}/reassign", ['assigned_employee_id' => $second->id, 'reason' => 'Rebalancing work'])
        ->assertSessionHas('success');

    $event = $task->events()->sole();

    expect($task->fresh()->assigned_employee_id)->toBe($second->id)
        ->and($task->fresh()->status)->toBe(RecruiterTaskStatus::Assigned)
        ->and($event->event)->toBe(RecruiterTaskEventType::Reassigned)
        ->and($event->from_employee_id)->toBe($first->id)
        ->and($event->to_employee_id)->toBe($second->id)
        ->and($event->reason)->toBe('Rebalancing work');
});

test('a task already being worked on cannot be reassigned', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $first = recruiterTaskStaff(SystemRole::Recruiter);
    $second = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($first)->createdBy($lead->user)->create();

    $this->actingAs($lead->user)
        ->post("/recruiter/tasks/{$task->id}/reassign", ['assigned_employee_id' => $second->id])
        ->assertSessionHas('error');

    expect($task->fresh()->assigned_employee_id)->toBe($first->id)
        ->and($task->events()->count())->toBe(0);
});

// C. Workflow and E. History

dataset('recruiter transitions', [
    'assigned to in progress (accept)' => ['assigned', 'accept', 'recruiter', [], 'in_progress', 'accepted'],
    'assigned to declined' => ['assigned', 'decline', 'recruiter', ['reason' => 'Out of capacity'], 'declined', 'declined'],
    'assigned to cancelled' => ['assigned', 'cancel', 'lead', ['reason' => 'Client paused'], 'cancelled', 'cancelled'],
    'in progress to on hold' => ['in_progress', 'hold', 'recruiter', ['reason' => 'Waiting on job description'], 'on_hold', 'put_on_hold'],
    'in progress to completed' => ['in_progress', 'complete', 'recruiter', ['achieved_count' => 12], 'completed', 'completed'],
    'in progress to cancelled' => ['in_progress', 'cancel', 'lead', ['reason' => 'No longer needed'], 'cancelled', 'cancelled'],
    'on hold to in progress (resume)' => ['on_hold', 'resume', 'recruiter', [], 'in_progress', 'resumed'],
    'on hold to cancelled' => ['on_hold', 'cancel', 'lead', ['reason' => 'Requirement closed'], 'cancelled', 'cancelled'],
    'declined to assigned (reassign)' => ['declined', 'reassign', 'lead', [], 'assigned', 'reassigned'],
    'completed to in progress (reopen)' => ['completed', 'reopen', 'lead', ['reason' => 'Count was short'], 'in_progress', 'reopened'],
]);

test('each allowed transition moves the status and writes exactly one matching event', function (
    string $from,
    string $action,
    string $actorRole,
    array $payload,
    string $to,
    string $event,
) {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->assignedTo($recruiter)->createdBy($lead->user)->create([
        'status' => $from,
        'accepted_at' => $from === 'assigned' || $from === 'declined' ? null : now(),
        'completed_at' => $from === 'completed' ? now() : null,
    ]);

    if ($action === 'reassign') {
        $payload['assigned_employee_id'] = recruiterTaskStaff(SystemRole::Recruiter)->id;
    }

    $actor = $actorRole === 'lead' ? $lead->user : $recruiter->user;

    $this->actingAs($actor)
        ->post("/recruiter/tasks/{$task->id}/{$action}", $payload)
        ->assertSessionHas('success');

    $recorded = $task->events()->get();

    expect($task->fresh()->status->value)->toBe($to)
        ->and($recorded)->toHaveCount(1)
        ->and($recorded[0]->event->value)->toBe($event)
        ->and($recorded[0]->from_status->value)->toBe($from)
        ->and($recorded[0]->to_status->value)->toBe($to)
        ->and($recorded[0]->actor_user_id)->toBe($actor->id);

    if (array_key_exists('reason', $payload)) {
        expect($recorded[0]->reason)->toBe($payload['reason']);
    }
})->with('recruiter transitions');

test('transitions outside the lifecycle are refused without an event', function (string $from, string $action, array $payload) {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->assignedTo($recruiter)->createdBy($lead->user)->create(['status' => $from]);

    $actor = in_array($action, ['reopen', 'cancel'], true) ? $lead->user : $recruiter->user;

    $this->actingAs($actor)
        ->post("/recruiter/tasks/{$task->id}/{$action}", $payload)
        ->assertSessionHas('error');

    expect($task->fresh()->status->value)->toBe($from)
        ->and($task->events()->count())->toBe(0);
})->with([
    'complete an unaccepted task' => ['assigned', 'complete', []],
    'resume a task that is not on hold' => ['in_progress', 'resume', []],
    'accept a task in progress' => ['in_progress', 'accept', []],
    'reopen an open task' => ['in_progress', 'reopen', ['reason' => 'x']],
    'cancel a completed task' => ['completed', 'cancel', ['reason' => 'x']],
]);

test('a cancelled task is terminal', function (string $action, string $actorRole, array $payload) {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->cancelled()->assignedTo($recruiter)->createdBy($lead->user)->create();

    if ($action === 'reassign') {
        $payload['assigned_employee_id'] = recruiterTaskStaff(SystemRole::Recruiter)->id;
    }

    $this->actingAs($actorRole === 'lead' ? $lead->user : $recruiter->user)
        ->post("/recruiter/tasks/{$task->id}/{$action}", $payload)
        ->assertSessionHas('error');

    expect($task->fresh()->status)->toBe(RecruiterTaskStatus::Cancelled)
        ->and($task->events()->count())->toBe(0);
})->with([
    'accept' => ['accept', 'recruiter', []],
    'resume' => ['resume', 'lead', []],
    'reopen' => ['reopen', 'lead', ['reason' => 'x']],
    'cancel again' => ['cancel', 'lead', ['reason' => 'x']],
    'reassign' => ['reassign', 'lead', []],
]);

test('a cancelled task cannot be edited', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $task = RecruiterTask::factory()->cancelled()->createdBy($lead->user)->create();

    $this->actingAs($lead->user)->get("/recruiter/tasks/{$task->id}/edit")->assertForbidden();
});

test('accepting stamps the acceptance and start times', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($recruiter);

    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/accept")->assertSessionHas('success');

    expect($task->fresh()->accepted_at)->not->toBeNull()
        ->and($task->fresh()->started_at)->not->toBeNull();
});

test('reopening clears the completion time', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $task = RecruiterTask::factory()->completed()->createdBy($lead->user)->create();

    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/reopen", ['reason' => 'Redo'])->assertSessionHas('success');

    expect($task->fresh()->completed_at)->toBeNull();
});

test('a reason is required to decline, hold, reopen and cancel', function (string $status, string $action, string $actorRole) {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->assignedTo($recruiter)->createdBy($lead->user)->create(['status' => $status]);

    $this->actingAs($actorRole === 'lead' ? $lead->user : $recruiter->user)
        ->post("/recruiter/tasks/{$task->id}/{$action}", ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($task->fresh()->status->value)->toBe($status);
})->with([
    'decline' => ['assigned', 'decline', 'recruiter'],
    'hold' => ['in_progress', 'hold', 'recruiter'],
    'reopen' => ['completed', 'reopen', 'lead'],
    'cancel' => ['assigned', 'cancel', 'lead'],
]);

test('editing details records one updated event listing the changed fields', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $task = RecruiterTask::factory()->createdBy($lead->user)->create([
        'title' => 'Old title',
        'target_count' => 5,
        'due_at' => now()->addDay()->startOfMinute(),
    ]);

    $this->actingAs($lead->user)
        ->put("/recruiter/tasks/{$task->id}", [
            'title' => 'New title',
            'description' => $task->description,
            'work_type' => $task->work_type->value,
            'priority' => $task->priority->value,
            'due_at' => $task->due_at->format('Y-m-d H:i'),
            'target_count' => 8,
        ])
        ->assertRedirect("/recruiter/tasks/{$task->id}");

    $event = $task->events()->sole();

    expect($event->event)->toBe(RecruiterTaskEventType::Updated)
        ->and(array_keys($event->metadata['changes']))->toEqualCanonicalizing(['title', 'target_count'])
        ->and($event->metadata['changes']['title'])->toEqual(['from' => 'Old title', 'to' => 'New title'])
        ->and($task->fresh()->status)->toBe(RecruiterTaskStatus::Assigned);
});

test('the edit form cannot change status or assignee', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $other = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($recruiter, $lead->user);

    $this->actingAs($lead->user)->put("/recruiter/tasks/{$task->id}", [
        'title' => $task->title,
        'work_type' => $task->work_type->value,
        'priority' => $task->priority->value,
        'status' => 'completed',
        'assigned_employee_id' => $other->id,
    ]);

    expect($task->fresh()->status)->toBe(RecruiterTaskStatus::Assigned)
        ->and($task->fresh()->assigned_employee_id)->toBe($recruiter->id);
});

test('an untouched task can be deleted but one with workflow history cannot', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);

    $this->actingAs($lead->user)->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter));
    $fresh = RecruiterTask::query()->sole();

    $this->actingAs($lead->user)->delete("/recruiter/tasks/{$fresh->id}")->assertRedirect('/recruiter/tasks');
    expect(RecruiterTask::query()->count())->toBe(0)
        ->and(RecruiterTaskEvent::query()->count())->toBe(0);

    $this->actingAs($lead->user)->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter));
    $worked = RecruiterTask::query()->sole();
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$worked->id}/decline", ['reason' => 'Busy']);
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$worked->id}/reassign", ['assigned_employee_id' => $recruiter->id]);

    expect($worked->fresh()->status)->toBe(RecruiterTaskStatus::Assigned);

    $this->actingAs($lead->user)->delete("/recruiter/tasks/{$worked->id}")->assertSessionHas('error');
    expect(RecruiterTask::query()->whereKey($worked->id)->exists())->toBeTrue();
});

test('the full lifecycle leaves a complete timeline on the task page', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);

    $this->actingAs($lead->user)->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter));
    $task = RecruiterTask::query()->sole();

    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/accept");
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/hold", ['reason' => 'Waiting']);
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/resume");
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/complete", ['achieved_count' => 18]);
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/reopen", ['reason' => 'Two short']);
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/complete", ['achieved_count' => 20]);

    expect(recruiterTaskEvents($task))->toBe([
        'created', 'assigned', 'accepted', 'put_on_hold', 'resumed', 'completed', 'reopened', 'completed',
    ]);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/tasks/{$task->id}")
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/tasks/show')
            ->has('timeline', 8)
            ->where('timeline.0.event', 'created')
            ->where('timeline.5.details', 'Achieved 18 of 20')
            ->where('timeline.6.reason', 'Two short')
            ->where('task.achieved_count', 20));
});

// D. Identity rules

test('only the assignee can accept, decline or complete', function (string $status, string $action, array $payload) {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $colleague = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->assignedTo($recruiter)->createdBy($lead->user)->create(['status' => $status]);

    $this->actingAs($colleague->user)->post("/recruiter/tasks/{$task->id}/{$action}", $payload)->assertForbidden();
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/{$action}", $payload)->assertForbidden();

    expect($task->fresh()->status->value)->toBe($status)
        ->and($task->events()->count())->toBe(0);

    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/{$action}", $payload)->assertSessionHas('success');
})->with([
    'accept' => ['assigned', 'accept', []],
    'decline' => ['assigned', 'decline', ['reason' => 'Busy']],
    'complete' => ['in_progress', 'complete', []],
]);

test('a manager can reopen and cancel', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $completed = RecruiterTask::factory()->completed()->assignedTo($recruiter)->create();
    $open = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->create();

    $this->actingAs($lead->user)->post("/recruiter/tasks/{$completed->id}/reopen", ['reason' => 'Short'])->assertSessionHas('success');
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$open->id}/cancel", ['reason' => 'Closed'])->assertSessionHas('success');

    expect($completed->fresh()->status)->toBe(RecruiterTaskStatus::InProgress)
        ->and($open->fresh()->status)->toBe(RecruiterTaskStatus::Cancelled);
});

test('super admin cannot accept, decline or complete another recruiter\'s task as that recruiter', function (string $status, string $action, array $payload) {
    $admin = superAdminEmployee();
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->assignedTo($recruiter)->create(['status' => $status]);

    $this->actingAs($admin->user)
        ->post("/recruiter/tasks/{$task->id}/{$action}", $payload)
        ->assertSessionHas('error');

    expect($task->fresh()->status->value)->toBe($status)
        ->and($task->events()->count())->toBe(0);

    $this->actingAs($admin->user)
        ->get("/recruiter/tasks/{$task->id}")
        ->assertInertia(fn ($page) => $page
            ->where('actions.accept', false)
            ->where('actions.decline', false)
            ->where('actions.complete', false));
})->with([
    'accept' => ['assigned', 'accept', []],
    'decline' => ['assigned', 'decline', ['reason' => 'x']],
    'complete' => ['in_progress', 'complete', []],
]);

test('super admin without an employee profile cannot act as a recruiter either', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->assignedTo($recruiter)->create();

    $this->actingAs(superAdmin())
        ->post("/recruiter/tasks/{$task->id}/accept")
        ->assertSessionHas('error');

    expect($task->fresh()->status)->toBe(RecruiterTaskStatus::Assigned);
});

test('super admin can still perform manager actions', function () {
    $admin = superAdmin();
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->assignedTo($recruiter)->create();

    $this->actingAs($admin)->post("/recruiter/tasks/{$task->id}/cancel", ['reason' => 'Closed'])->assertSessionHas('success');

    expect($task->fresh()->status)->toBe(RecruiterTaskStatus::Cancelled);
});

test('the task page offers only the actions the viewer may take', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($recruiter, $lead->user);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/tasks/{$task->id}")
        ->assertInertia(fn ($page) => $page
            ->where('actions.accept', true)
            ->where('actions.decline', true)
            ->where('actions.cancel', false)
            ->where('actions.reassign', false)
            ->where('actions.edit', false)
            ->where('recruiters', []));

    $this->actingAs($lead->user)
        ->get("/recruiter/tasks/{$task->id}")
        ->assertInertia(fn ($page) => $page
            ->where('actions.accept', false)
            ->where('actions.cancel', true)
            ->where('actions.reassign', true)
            ->where('actions.edit', true)
            ->where('actions.delete', true));
});

// F. Separation

test('recruiter tasks never appear in digital marketing task management', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    RecruiterTask::factory()->assignedTo($recruiter)->create(['title' => 'Recruiter only work']);
    $marketingTask = Task::factory()->create(['title' => 'Marketing campaign']);

    $this->actingAs(employeeWith(Ability::AccessTasks, Ability::ViewAllTasks)->user)
        ->get('/tasks')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/index')
            ->where('tasks.data', fn ($tasks) => collect($tasks)->pluck('title')->contains('Marketing campaign')
                && ! collect($tasks)->pluck('title')->contains('Recruiter only work')));

    expect(Task::query()->count())->toBe(1)
        ->and($marketingTask->getTable())->not->toBe((new RecruiterTask)->getTable());
});

test('digital marketing tasks never appear in recruiter tasks', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    Task::factory()->create(['title' => 'Marketing campaign', 'assigned_employee_id' => $recruiter->id]);
    recruiterTaskFor($recruiter, $lead->user);

    $this->actingAs($lead->user)
        ->get('/recruiter/tasks')
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 1)
            ->where('tasks.data', fn ($tasks) => ! collect($tasks)->pluck('title')->contains('Marketing campaign')));

    $this->actingAs($recruiter->user)
        ->get('/recruiter/tasks')
        ->assertInertia(fn ($page) => $page->has('tasks.data', 1));
});

// G. Notifications

test('assignment notifies the recruiter and never the manager who assigned it', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);

    $this->actingAs($lead->user)->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter));
    $task = RecruiterTask::query()->sole();

    $notification = $recruiter->user->notifications()->sole();

    expect($notification->data['event'])->toBe('recruiter.task.assigned')
        ->and($notification->data['url'])->toBe("/recruiter/tasks/{$task->id}")
        ->and($notification->data['recruiter_task_id'])->toBe($task->id)
        ->and($notification->data['actor']['id'])->toBe($lead->user->id)
        ->and($notification->data)->not->toHaveKey('task_id')
        ->and($lead->user->notifications()->count())->toBe(0);
});

test('accepting and declining notify the manager, not the recruiter', function (string $action, array $payload, string $event) {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($recruiter, $lead->user);

    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/{$action}", $payload);

    expect(recruiterNotificationEvents($lead->user))->toBe([$event])
        ->and($recruiter->user->notifications()->count())->toBe(0);
})->with([
    'accept' => ['accept', [], 'recruiter.task.accepted'],
    'decline' => ['decline', ['reason' => 'Busy'], 'recruiter.task.declined'],
]);

test('reassignment notifies the previous and the new recruiter', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $first = recruiterTaskStaff(SystemRole::Recruiter);
    $second = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($first, $lead->user);

    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/reassign", ['assigned_employee_id' => $second->id]);

    expect(recruiterNotificationEvents($first->user))->toBe(['recruiter.task.reassigned_away'])
        ->and(recruiterNotificationEvents($second->user))->toBe(['recruiter.task.assigned'])
        ->and($lead->user->notifications()->count())->toBe(0);
});

test('completion notifies the creator and the manager who assigned it', function () {
    $creator = recruiterTaskStaff(SystemRole::RecruiterLead);
    $assigner = recruiterTaskStaff(SystemRole::RecruiterLead);
    $first = recruiterTaskStaff(SystemRole::Recruiter);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($first, $creator->user);

    $this->actingAs($assigner->user)->post("/recruiter/tasks/{$task->id}/reassign", ['assigned_employee_id' => $recruiter->id]);
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/accept");
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/complete", ['achieved_count' => 4]);

    expect(recruiterNotificationEvents($creator->user))->toBe(['recruiter.task.accepted', 'recruiter.task.completed'])
        ->and(recruiterNotificationEvents($assigner->user))->toBe(['recruiter.task.accepted', 'recruiter.task.completed'])
        ->and(recruiterNotificationEvents($recruiter->user))->toBe(['recruiter.task.assigned']);
});

test('a manager putting a task on hold notifies the recruiter but not themselves', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->createdBy($lead->user)->create();

    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/hold", ['reason' => 'Client paused']);

    expect(recruiterNotificationEvents($recruiter->user))->toBe(['recruiter.task.put_on_hold'])
        ->and($lead->user->notifications()->count())->toBe(0);
});

test('inactive users are not notified', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($recruiter, $lead->user);
    $lead->user->forceFill(['is_active' => false])->save();

    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/accept")->assertSessionHas('success');

    expect($lead->user->notifications()->count())->toBe(0);
});

test('no notification is sent when a workflow move fails', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->createdBy($lead->user)->create();

    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/accept")->assertSessionHas('error');

    expect($lead->user->notifications()->count())->toBe(0);
});

// H. Target counts

test('the target count is optional', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);

    $this->actingAs($lead->user)
        ->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter, ['target_count' => null]))
        ->assertSessionHasNoErrors();

    expect(RecruiterTask::query()->sole()->target_count)->toBeNull();
});

test('the target count must be a positive whole number', function (mixed $value) {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);

    $this->actingAs($lead->user)
        ->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter, ['target_count' => $value]))
        ->assertSessionHasErrors('target_count');
})->with([0, -3, 'twenty', 100001]);

test('the achieved count is validated and cannot be negative', function (mixed $value) {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->create(['target_count' => 10]);

    $this->actingAs($recruiter->user)
        ->post("/recruiter/tasks/{$task->id}/complete", ['achieved_count' => $value])
        ->assertSessionHasErrors('achieved_count');

    expect($task->fresh()->status)->toBe(RecruiterTaskStatus::InProgress)
        ->and($task->events()->count())->toBe(0);
})->with([-1, 'lots', 100001, 2.5]);

test('completion stores the achieved count and the note', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->create(['target_count' => 10]);

    $this->actingAs($recruiter->user)
        ->post("/recruiter/tasks/{$task->id}/complete", ['achieved_count' => 7, 'completion_note' => 'Three declined interviews'])
        ->assertSessionHas('success');

    $task->refresh();
    $event = $task->events()->sole();

    expect($task->achieved_count)->toBe(7)
        ->and($task->completion_note)->toBe('Three declined interviews')
        ->and($task->completed_at)->not->toBeNull()
        ->and($event->metadata)->toEqual([
            'target_count' => 10,
            'achieved_count' => 7,
            'completion_note' => 'Three declined interviews',
        ]);
});

test('completion without a count or note is allowed', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->create();

    $this->actingAs($recruiter->user)
        ->post("/recruiter/tasks/{$task->id}/complete", ['achieved_count' => '', 'completion_note' => ''])
        ->assertSessionHas('success');

    expect($task->fresh()->status)->toBe(RecruiterTaskStatus::Completed)
        ->and($task->fresh()->achieved_count)->toBeNull();
});

// I. Authorization

test('a recruiter cannot edit, cancel, reopen, reassign or delete another recruiter\'s task', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $colleague = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($colleague);
    $completed = RecruiterTask::factory()->completed()->assignedTo($colleague)->create();

    $this->actingAs($recruiter->user)->get("/recruiter/tasks/{$task->id}/edit")->assertForbidden();
    $this->actingAs($recruiter->user)->put("/recruiter/tasks/{$task->id}", ['title' => 'Hijacked'])->assertForbidden();
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/cancel", ['reason' => 'x'])->assertForbidden();
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/cancel")->assertForbidden();
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$completed->id}/reopen", ['reason' => 'x'])->assertForbidden();
    $this->actingAs($recruiter->user)
        ->post("/recruiter/tasks/{$task->id}/reassign", ['assigned_employee_id' => $recruiter->id])
        ->assertForbidden();
    $this->actingAs($recruiter->user)->delete("/recruiter/tasks/{$task->id}")->assertForbidden();

    expect($task->fresh()->title)->not->toBe('Hijacked')
        ->and($task->fresh()->status)->toBe(RecruiterTaskStatus::Assigned)
        ->and($completed->fresh()->status)->toBe(RecruiterTaskStatus::Completed);
});

test('a recruiter cannot cancel or reopen even their own task', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($recruiter);
    $completed = RecruiterTask::factory()->completed()->assignedTo($recruiter)->create();

    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/cancel", ['reason' => 'x'])->assertForbidden();
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$completed->id}/reopen", ['reason' => 'x'])->assertForbidden();
});

test('a recruiter lead can perform every manager action', function () {
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $other = recruiterTaskStaff(SystemRole::Recruiter);
    $task = recruiterTaskFor($recruiter);

    $this->actingAs($lead->user)->get("/recruiter/tasks/{$task->id}/edit")->assertOk();
    $this->actingAs($lead->user)->put("/recruiter/tasks/{$task->id}", [
        'title' => 'Renamed by lead',
        'work_type' => 'follow_up',
        'priority' => 'urgent',
    ])->assertRedirect("/recruiter/tasks/{$task->id}");
    $this->actingAs($lead->user)
        ->post("/recruiter/tasks/{$task->id}/reassign", ['assigned_employee_id' => $other->id])
        ->assertSessionHas('success');
    $this->actingAs($other->user)->post("/recruiter/tasks/{$task->id}/accept");
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/hold", ['reason' => 'Paused'])->assertSessionHas('success');
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/resume")->assertSessionHas('success');
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/cancel", ['reason' => 'Closed'])->assertSessionHas('success');

    expect($task->fresh()->title)->toBe('Renamed by lead')
        ->and($task->fresh()->status)->toBe(RecruiterTaskStatus::Cancelled);
});

test('a manager without team visibility manages only the tasks they created', function () {
    $manager = recruiterTaskStaff(SystemRole::Recruiter);
    $manager->user->givePermissionTo(Ability::ManageRecruiterTasks->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $own = recruiterTaskFor($recruiter, $manager->user);
    $foreign = recruiterTaskFor($recruiter);

    $this->actingAs($manager->user)->post("/recruiter/tasks/{$own->id}/cancel", ['reason' => 'x'])->assertSessionHas('success');
    $this->actingAs($manager->user)->post("/recruiter/tasks/{$foreign->id}/cancel", ['reason' => 'x'])->assertForbidden();
    $this->actingAs($manager->user)->get("/recruiter/tasks/{$foreign->id}")->assertForbidden();
});

// Dashboard and exports

test('the dashboard shows a recruiter their task counts without rankings', function () {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    $lead = recruiterTaskStaff(SystemRole::RecruiterLead);

    $this->actingAs($lead->user)->post('/recruiter/tasks', validRecruiterTaskPayload($recruiter, ['due_at' => now()->endOfDay()->subMinute()->format('Y-m-d H:i')]));
    RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->create();
    RecruiterTask::factory()->completed()->assignedTo($recruiter)->create();

    $this->actingAs($recruiter->user)
        ->get('/recruiter')
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/dashboard')
            ->where('myTasks.assigned_today', 1)
            ->where('myTasks.in_progress', 1)
            ->where('myTasks.pending', 2)
            ->where('myTasks.completed_today', 1)
            ->where('myTasks.due_today', 1)
            ->where('teamTasks', null));

    $this->actingAs($lead->user)
        ->get('/recruiter')
        ->assertInertia(fn ($page) => $page
            ->where('teamTasks.assigned', 1)
            ->where('teamTasks.in_progress', 1)
            ->where('teamTasks.completed_today', 1)
            ->missing('teamTasks.ranking'));
});

test('recruiter tasks export to excel and pdf within the viewer\'s visibility', function (string $format) {
    $recruiter = recruiterTaskStaff(SystemRole::Recruiter);
    recruiterTaskFor($recruiter);

    $this->actingAs($recruiter->user)->get("/recruiter/tasks/export/{$format}")->assertOk();
    $this->actingAs(staffWith())->get("/recruiter/tasks/export/{$format}")->assertForbidden();
})->with(['excel', 'pdf']);
