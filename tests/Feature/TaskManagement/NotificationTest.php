<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\AssignmentAction;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\Timesheet;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function notifyManager()
{
    return employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
        Ability::ReviewDeliverables,
        Ability::ApproveTimesheets,
    );
}

function notifyWorker()
{
    return employeeWith(Ability::AccessTasks);
}

test('assigning a task notifies the assignee and not the assigner', function () {
    Notification::fake();

    $manager = notifyManager();
    $employee = notifyWorker();
    $task = Task::factory()->create(['created_by_user_id' => $manager->user_id]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id])
        ->assertRedirect();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($task) {
        return $notification->payload['event'] === 'task.assigned'
            && $notification->payload['task_id'] === $task->id
            && $notification->payload['title'] === 'New Task Assigned'
            && $notification->payload['body'] === "You have been assigned to the task: {$task->title}"
            && $notification->payload['url'] === "/tasks/{$task->id}";
    });

    Notification::assertNotSentTo($manager->user, StaffDatabaseNotification::class);
});

test('creating and assigning a task in one step notifies the assignee', function () {
    Notification::fake();

    $manager = notifyManager();
    $employee = notifyWorker();
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Poster design',
            'type' => 'design',
            'priority' => 'normal',
            'assigned_employee_id' => $employee->id,
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($task) {
        return $notification->payload['event'] === 'task.assigned'
            && $notification->payload['task_id'] === $task->id;
    });
});

test('assignment notifications are stored in the database even when broadcast fails', function () {
    config(['broadcasting.default' => 'reverb']);

    $manager = notifyManager();
    $employee = notifyWorker();
    $task = Task::factory()->create(['created_by_user_id' => $manager->user_id]);

    Broadcast::shouldReceive('connection')->andThrow(new RuntimeException('Reverb unavailable'));

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id])
        ->assertRedirect();

    $notification = $employee->user->notifications()->sole();

    expect($notification->data['event'] ?? null)->toBe('task.assigned')
        ->and($notification->data['title'] ?? null)->toBe('New Task Assigned')
        ->and($notification->read_at)->toBeNull();
});

test('reassigning notifies the previous and new assignees', function () {
    Notification::fake();

    $manager = notifyManager();
    $first = notifyWorker();
    $second = notifyWorker();
    $task = Task::factory()->create(['created_by_user_id' => $manager->user_id]);

    $this->actingAs($manager->user)->post("/tasks/{$task->id}/assign", ['employee_id' => $first->id]);
    Notification::fake();

    $this->actingAs($manager->user)->post("/tasks/{$task->id}/assign", ['employee_id' => $second->id]);

    Notification::assertSentTo($first->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.reassigned_away';
    });

    Notification::assertSentTo($second->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.assigned';
    });
});

test('accepting notifies oversight users that work is in progress', function () {
    Notification::fake();

    $manager = notifyManager();
    $employee = notifyWorker();
    $task = Task::factory()->create([
        'status' => TaskStatus::Assigned,
        'assigned_employee_id' => $employee->id,
        'created_by_user_id' => $manager->user_id,
    ]);
    $task->assignments()->create([
        'employee_id' => $employee->id,
        'assigned_by_user_id' => $manager->user_id,
        'mode' => AssignmentAction::Direct,
        'status' => AssignmentStatus::Pending,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/accept")->assertRedirect();

    Notification::assertSentTo($manager->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.in_progress';
    });

    Notification::assertNotSentTo($employee->user, StaffDatabaseNotification::class);
});

test('claiming notifies the task creator', function () {
    Notification::fake();

    $manager = notifyManager();
    $employee = notifyWorker();
    $task = Task::factory()->open()->create([
        'created_by_user_id' => $manager->user_id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/claim")->assertRedirect();

    Notification::assertSentTo($manager->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.claimed';
    });
});

test('claiming notifies the project manager and reporting manager when they differ from the actor', function () {
    Notification::fake();

    $creator = notifyManager();
    $projectManager = notifyManager();
    $reportingManager = notifyManager();
    $employee = notifyWorker();

    $employee->update(['reporting_to_id' => $reportingManager->id]);

    $project = Project::factory()->create([
        'manager_employee_id' => $projectManager->id,
    ]);

    $task = Task::factory()->open()->create([
        'tm_project_id' => $project->id,
        'created_by_user_id' => $creator->user_id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/claim")->assertRedirect();

    Notification::assertSentTo($creator->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.claimed');
    Notification::assertSentTo($projectManager->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.claimed');
    Notification::assertSentTo($reportingManager->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.claimed');
    Notification::assertNotSentTo($employee->user, StaffDatabaseNotification::class);
});

test('declining notifies the creator and assigner', function () {
    Notification::fake();

    $manager = notifyManager();
    $employee = notifyWorker();
    $task = Task::factory()->create([
        'status' => TaskStatus::Assigned,
        'assigned_employee_id' => $employee->id,
        'created_by_user_id' => $manager->user_id,
    ]);
    $task->assignments()->create([
        'employee_id' => $employee->id,
        'assigned_by_user_id' => $manager->user_id,
        'mode' => AssignmentAction::Direct,
        'status' => AssignmentStatus::Pending,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/decline", ['reason' => 'Too busy'])
        ->assertRedirect();

    Notification::assertSentTo($manager->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.declined';
    });

    Notification::assertNotSentTo($employee->user, StaffDatabaseNotification::class);
});

test('publishing to the open board notifies withdrawn assignees, the creator, and eligible employees', function () {
    Notification::fake();

    $manager = notifyManager();
    $employee = notifyWorker();
    $otherWorker = notifyWorker();
    $creator = notifyManager();
    $task = Task::factory()->create([
        'status' => TaskStatus::Assigned,
        'assigned_employee_id' => $employee->id,
        'created_by_user_id' => $creator->user_id,
    ]);
    $task->assignments()->create([
        'employee_id' => $employee->id,
        'assigned_by_user_id' => $manager->user_id,
        'mode' => AssignmentAction::Direct,
        'status' => AssignmentStatus::Pending,
    ]);

    $this->actingAs($manager->user)->post("/tasks/{$task->id}/publish")->assertRedirect();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.reassigned_away');
    Notification::assertSentTo($creator->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.published');
    Notification::assertSentTo($otherWorker->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.open_available'
        && $n->payload['title'] === 'New Open Task Available');
});

test('starting a task notifies oversight users but not the actor', function () {
    Notification::fake();

    $creator = notifyManager();
    $projectManager = notifyManager();
    $employee = notifyWorker();

    $project = Project::factory()->create([
        'manager_employee_id' => $projectManager->id,
    ]);

    $task = Task::factory()->create([
        'status' => TaskStatus::Assigned,
        'assigned_employee_id' => $employee->id,
        'tm_project_id' => $project->id,
        'created_by_user_id' => $creator->user_id,
    ]);
    $task->assignments()->create([
        'employee_id' => $employee->id,
        'assigned_by_user_id' => $creator->user_id,
        'mode' => AssignmentAction::Direct,
        'status' => AssignmentStatus::Pending,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/accept")
        ->assertRedirect();

    Notification::assertSentTo($creator->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.in_progress');
    Notification::assertSentTo($projectManager->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.in_progress');
    Notification::assertNotSentTo($employee->user, StaffDatabaseNotification::class);
});

test('client approval notifies oversight users and the assignee', function () {
    Notification::fake();

    $creator = notifyManager();
    $projectManager = notifyManager();
    $employee = notifyWorker();
    $reviewer = notifyManager();

    $project = Project::factory()->create([
        'manager_employee_id' => $projectManager->id,
    ]);

    $task = Task::factory()->create([
        'status' => TaskStatus::InReview,
        'assigned_employee_id' => $employee->id,
        'tm_project_id' => $project->id,
        'created_by_user_id' => $creator->user_id,
    ]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $employee->id,
        'status' => \App\Modules\TaskManagement\Enums\DeliverableStatus::Approved,
    ]);
    $link = app(\App\Modules\TaskManagement\Services\DeliverableShareLinkService::class)->getOrCreate($deliverable, $reviewer->user);

    $this->post(route('share.approve', ['token' => $link->token]))->assertRedirect();

    Notification::assertSentTo($creator->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.client_approved');
    Notification::assertSentTo($projectManager->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.client_approved');
    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, fn (StaffDatabaseNotification $n) => $n->payload['event'] === 'task.client_approved');
});

test('requesting proof changes notifies the assignee', function () {
    Notification::fake();
    Storage::fake('public');

    $employee = notifyWorker();
    $reviewer = notifyManager();
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('hero.jpg')],
    ]);

    Notification::fake();

    $deliverable = Deliverable::query()->sole();

    $this->actingAs($reviewer->user)
        ->post("/tasks/deliverables/{$deliverable->id}/review", [
            'decision' => 'request_changes',
            'comments' => 'Adjust spacing',
        ])
        ->assertRedirect();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.proof_request_changes';
    });
});

test('rejecting a proof notifies the assignee', function () {
    Notification::fake();
    Storage::fake('public');

    $employee = notifyWorker();
    $reviewer = notifyManager();
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('hero.jpg')],
    ]);

    Notification::fake();

    $deliverable = Deliverable::query()->sole();

    $this->actingAs($reviewer->user)
        ->post("/tasks/deliverables/{$deliverable->id}/review", [
            'decision' => 'reject',
        ])
        ->assertRedirect();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.proof_reject';
    });
});

test('rejecting a timesheet notifies the owner', function () {
    Notification::fake();

    $employee = notifyWorker();
    $manager = notifyManager();
    $sheet = Timesheet::factory()->submitted()->create(['employee_id' => $employee->id]);

    $this->actingAs($manager->user)->post("/tasks/timesheets/{$sheet->id}/reject")->assertRedirect();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'timesheet.rejected';
    });
});

test('submitting a proof notifies reviewers', function () {
    Notification::fake();
    Storage::fake('public');

    $employee = notifyWorker();
    $reviewer = notifyManager();
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
        'created_by_user_id' => $reviewer->user_id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('hero.jpg')],
    ])->assertRedirect();

    Notification::assertSentTo($reviewer->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.proof_submitted';
    });
});

test('approving a proof notifies the assignee', function () {
    Notification::fake();
    Storage::fake('public');

    $employee = notifyWorker();
    $reviewer = notifyManager();
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('hero.jpg')],
    ]);

    Notification::fake();

    $deliverable = Deliverable::query()->sole();

    $this->actingAs($reviewer->user)
        ->post("/tasks/deliverables/{$deliverable->id}/review", [
            'decision' => 'approve',
        ])
        ->assertRedirect();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.proof_approve';
    });
});

test('submitting a timesheet notifies approvers', function () {
    Notification::fake();

    $employee = notifyWorker();
    $manager = notifyManager();
    $sheet = Timesheet::factory()->create(['employee_id' => $employee->id]);

    $this->actingAs($employee->user)->post("/tasks/timesheets/{$sheet->id}/submit")->assertRedirect();

    Notification::assertSentTo($manager->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($sheet) {
        return $notification->payload['event'] === 'timesheet.submitted'
            && $notification->payload['timesheet_id'] === $sheet->id;
    });

    Notification::assertNotSentTo($employee->user, StaffDatabaseNotification::class);
});

test('approving a timesheet notifies the owner', function () {
    Notification::fake();

    $employee = notifyWorker();
    $manager = notifyManager();
    $sheet = Timesheet::factory()->submitted()->create(['employee_id' => $employee->id]);

    $this->actingAs($manager->user)->post("/tasks/timesheets/{$sheet->id}/approve")->assertRedirect();

    expect($sheet->refresh()->status)->toBe(TimesheetStatus::Approved);

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'timesheet.approved';
    });
});

test('a user can mark their notification as read and cannot mark someone elses', function () {
    $employee = notifyWorker();
    $other = notifyWorker();

    $employee->user->notify(new StaffDatabaseNotification([
        'event' => 'task.assigned',
        'title' => 'Test',
        'body' => 'Body',
        'url' => '/tasks/1',
        'task_id' => 1,
    ]));

    $notification = $employee->user->notifications()->sole();

    $this->actingAs($employee->user)
        ->post("/notifications/{$notification->id}/read")
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();

    $other->user->notify(new StaffDatabaseNotification([
        'event' => 'task.assigned',
        'title' => 'Other',
        'body' => 'Body',
        'url' => '/tasks/2',
        'task_id' => 2,
    ]));

    $foreign = $other->user->notifications()->sole();

    $this->actingAs($employee->user)
        ->post("/notifications/{$foreign->id}/read")
        ->assertNotFound();
});

test('mark all read clears unread notifications', function () {
    $employee = notifyWorker();

    $employee->user->notify(new StaffDatabaseNotification([
        'event' => 'task.assigned',
        'title' => 'One',
        'body' => 'Body',
        'url' => '/tasks/1',
    ]));
    $employee->user->notify(new StaffDatabaseNotification([
        'event' => 'task.assigned',
        'title' => 'Two',
        'body' => 'Body',
        'url' => '/tasks/2',
    ]));

    expect($employee->user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($employee->user)
        ->post('/notifications/read-all')
        ->assertRedirect();

    expect($employee->user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('the notifications inbox page renders for internal users', function () {
    $this->withoutVite();

    $employee = notifyWorker();

    $this->actingAs($employee->user)
        ->get('/notifications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Core/notifications/index'));
});
