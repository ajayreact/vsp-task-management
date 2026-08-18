<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\RecurrenceFrequency;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskChecklistItem;
use App\Modules\TaskManagement\Models\TaskComment;
use App\Modules\TaskManagement\Models\TaskRecurrenceRule;
use App\Modules\TaskManagement\Models\TaskSubtask;
use App\Modules\TaskManagement\Services\RecurringTaskService;

function completeTaskThroughWorkflow(Task $task, Employee $employee): void
{
    test()->actingAs($employee->user)
        ->post("/tasks/{$task->id}/status", ['status' => TaskStatus::InProgress->value])
        ->assertRedirect();

    test()->actingAs($employee->user)
        ->post("/tasks/{$task->id}/status", ['status' => TaskStatus::Completed->value])
        ->assertRedirect();
}

function createRecurrenceRule(Task $task, array $overrides = []): TaskRecurrenceRule
{
    $rule = TaskRecurrenceRule::query()->create([
        'source_tm_task_id' => $task->id,
        'created_by_user_id' => $task->created_by_user_id,
        'frequency' => RecurrenceFrequency::Daily,
        'interval' => 1,
        'start_date' => now()->toDateString(),
        'end_date' => null,
        'max_occurrences' => null,
        'occurrences_generated' => 0,
        'is_active' => true,
        ...$overrides,
    ]);

    $task->update([
        'tm_recurrence_rule_id' => $rule->id,
        'recurrence_occurrence_number' => 0,
    ]);

    return $rule;
}

test('an authorized user can configure recurrence on the source task', function () {
    $employee = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
        'created_by_user_id' => $employee->user_id,
    ]);

    $this->actingAs($employee->user)
        ->put("/tasks/{$task->id}/recurrence", [
            'frequency' => RecurrenceFrequency::Weekly->value,
            'interval' => 2,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'max_occurrences' => 5,
            'is_active' => true,
        ])
        ->assertRedirect();

    $rule = TaskRecurrenceRule::query()->sole();

    expect($rule->source_tm_task_id)->toBe($task->id)
        ->and($rule->frequency)->toBe(RecurrenceFrequency::Weekly)
        ->and($rule->interval)->toBe(2)
        ->and($rule->max_occurrences)->toBe(5)
        ->and($task->fresh()->recurrence_occurrence_number)->toBe(0);
});

test('generated occurrences cannot configure recurrence', function () {
    $employee = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::Draft,
        'assigned_employee_id' => $employee->id,
        'created_by_user_id' => $employee->user_id,
        'recurrence_occurrence_number' => 2,
    ]);

    $this->actingAs($employee->user)
        ->put("/tasks/{$task->id}/recurrence", [
            'frequency' => RecurrenceFrequency::Daily->value,
            'interval' => 1,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ])
        ->assertForbidden();
});

test('completing a daily recurring task generates the next occurrence', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'title' => 'Daily standup notes',
        'description' => 'Capture blockers.',
        'due_at' => now()->addDay(),
    ]);

    createRecurrenceRule($task, ['frequency' => RecurrenceFrequency::Daily, 'interval' => 1]);

    completeTaskThroughWorkflow($task, $employee);

    $generated = Task::query()->where('tm_recurrence_rule_id', $task->tm_recurrence_rule_id)
        ->where('recurrence_occurrence_number', 1)
        ->first();

    expect($generated)->not->toBeNull()
        ->and($generated->title)->toBe('Daily standup notes')
        ->and($generated->description)->toBe('Capture blockers.')
        ->and($generated->status)->toBe(TaskStatus::Draft)
        ->and($generated->assigned_employee_id)->toBe($employee->id)
        ->and($generated->due_at?->toDateString())->toBe(now()->addDays(2)->toDateString());
});

test('weekly recurrence shifts the due date by the configured interval', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'due_at' => now()->addWeek(),
    ]);

    createRecurrenceRule($task, ['frequency' => RecurrenceFrequency::Weekly, 'interval' => 1]);

    completeTaskThroughWorkflow($task, $employee);

    $generated = Task::query()->where('recurrence_occurrence_number', 1)->sole();

    expect($generated->due_at?->toDateString())->toBe(now()->addWeeks(2)->toDateString());
});

test('monthly recurrence shifts the due date by the configured interval', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'due_at' => now()->addMonth(),
    ]);

    createRecurrenceRule($task, ['frequency' => RecurrenceFrequency::Monthly, 'interval' => 1]);

    completeTaskThroughWorkflow($task, $employee);

    $generated = Task::query()->where('recurrence_occurrence_number', 1)->sole();

    expect($generated->due_at?->toDateString())->toBe(now()->addMonths(2)->toDateString());
});

test('inactive recurrence does not generate another task', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    createRecurrenceRule($task, ['is_active' => false]);

    completeTaskThroughWorkflow($task, $employee);

    expect(Task::query()->where('recurrence_occurrence_number', 1)->exists())->toBeFalse();
});

test('recurrence stops when the end date is reached', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'due_at' => now()->addDay(),
    ]);

    createRecurrenceRule($task, [
        'frequency' => RecurrenceFrequency::Daily,
        'end_date' => now()->addDay()->toDateString(),
    ]);

    completeTaskThroughWorkflow($task, $employee);

    expect(Task::query()->where('recurrence_occurrence_number', 1)->exists())->toBeFalse();
});

test('recurrence stops when the maximum occurrence count is reached', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'due_at' => now()->addDay(),
    ]);

    $rule = createRecurrenceRule($task, ['max_occurrences' => 1]);

    completeTaskThroughWorkflow($task, $employee);

    expect(Task::query()->where('recurrence_occurrence_number', 1)->exists())->toBeTrue()
        ->and($rule->fresh()->occurrences_generated)->toBe(1);

    $generated = Task::query()->where('recurrence_occurrence_number', 1)->sole();
    $generated->update(['status' => TaskStatus::Completed, 'completed_at' => now()]);

    expect(app(RecurringTaskService::class)->generateNextOccurrence($generated))->toBeNull()
        ->and(Task::query()->where('recurrence_occurrence_number', 2)->exists())->toBeFalse();
});

test('generated tasks copy checklist and subtask templates as new incomplete records', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    TaskChecklistItem::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Draft outline',
        'is_completed' => true,
        'sort_order' => 1,
    ]);

    TaskSubtask::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Collect assets',
        'status' => SubtaskStatus::Completed,
        'sort_order' => 1,
    ]);

    TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $employee->user_id,
        'body' => 'Do not copy me',
    ]);

    createRecurrenceRule($task);

    completeTaskThroughWorkflow($task, $employee);

    $generated = Task::query()->where('recurrence_occurrence_number', 1)->sole();

    expect($generated->checklistItems()->count())->toBe(1)
        ->and($generated->checklistItems()->sole()->is_completed)->toBeFalse()
        ->and($generated->subtasks()->count())->toBe(1)
        ->and($generated->subtasks()->sole()->status)->toBe(SubtaskStatus::Pending)
        ->and($generated->comments()->count())->toBe(0);
});

test('multiple scheduler runs remain idempotent for recurring tasks', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'status' => TaskStatus::Completed,
        'completed_at' => now(),
    ]);

    createRecurrenceRule($task);

    $service = app(RecurringTaskService::class);

    expect($service->processPendingGenerations())->toBe(1)
        ->and($service->processPendingGenerations())->toBe(0)
        ->and(Task::query()->where('recurrence_occurrence_number', 1)->count())->toBe(1);
});

test('the recurring task command is scheduled to run every minute', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('tasks:process-recurring')
        ->assertSuccessful();
});
