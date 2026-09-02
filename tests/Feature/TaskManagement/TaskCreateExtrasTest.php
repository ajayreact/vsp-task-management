<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskChecklistItem;
use App\Modules\TaskManagement\Models\TaskSubtask;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('creating a task can save a detailed requirement brief', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $requirement = "Create a 30-second promotional video.\n\nScene 1: Opening shot\nScene 2: Product demo\n\nUse brand colors.\nhttps://example.com/reference";

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Promotional video',
            'type' => 'design',
            'priority' => 'normal',
            'requirement' => $requirement,
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->requirement)->toBe($requirement);
});

test('updating a task can change the requirement brief', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $task = Task::factory()->create([
        'created_by_user_id' => $author->user->id,
        'requirement' => 'Original brief',
    ]);

    $this->actingAs($author->user)
        ->put("/tasks/{$task->id}", [
            'tm_project_id' => $task->tm_project_id,
            'title' => $task->title,
            'type' => $task->type->value,
            'priority' => $task->priority->value,
            'requirement' => "Updated brief\n\nLine two",
        ])
        ->assertRedirect();

    expect($task->fresh()->requirement)->toBe("Updated brief\n\nLine two");
});

test('creating a task without extras still works', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Simple launch task',
            'type' => 'design',
            'priority' => 'normal',
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->title)->toBe('Simple launch task')
        ->and($task->status)->toBe(TaskStatus::Draft)
        ->and($task->checklistItems)->toHaveCount(0)
        ->and($task->subtasks)->toHaveCount(0)
        ->and($task->getMedia('attachments'))->toHaveCount(0);
});

test('creating a task can save checklist items in order', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageTasks, Ability::ViewAllTasks);
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Checklist task',
            'type' => 'design',
            'priority' => 'normal',
            'checklist' => [
                ['title' => 'Review design'],
                ['title' => '  '],
                ['title' => 'Get approval'],
            ],
        ])
        ->assertRedirect();

    $task = Task::query()->sole();
    $items = TaskChecklistItem::query()->where('tm_task_id', $task->id)->orderBy('sort_order')->get();

    expect($items)->toHaveCount(2)
        ->and($items[0]->title)->toBe('Review design')
        ->and($items[0]->sort_order)->toBe(1)
        ->and($items[1]->title)->toBe('Get approval')
        ->and($items[1]->sort_order)->toBe(2);
});

test('creating a task can save subtasks for the parent task', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageTasks, Ability::AssignTasks, Ability::ViewAllTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Subtask parent',
            'type' => 'design',
            'priority' => 'normal',
            'subtasks' => [
                [
                    'title' => 'Create initial design',
                    'assigned_employee_id' => $assignee->id,
                    'due_at' => now()->addDay()->toIso8601String(),
                ],
                [
                    'title' => 'Review with client',
                ],
            ],
        ])
        ->assertRedirect();

    $task = Task::query()->sole();
    $subtasks = TaskSubtask::query()->where('tm_task_id', $task->id)->orderBy('sort_order')->get();

    expect($subtasks)->toHaveCount(2)
        ->and($subtasks[0]->title)->toBe('Create initial design')
        ->and($subtasks[0]->assigned_employee_id)->toBe($assignee->id)
        ->and($subtasks[0]->status)->toBe(SubtaskStatus::Pending)
        ->and($subtasks[1]->title)->toBe('Review with client');
});

test('creating a task can attach working files', function () {
    Storage::fake('public');

    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Attachment task',
            'type' => 'design',
            'priority' => 'normal',
            'files' => [
                UploadedFile::fake()->create('brief.pdf', 120, 'application/pdf'),
            ],
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->getMedia('attachments'))->toHaveCount(1)
        ->and($task->getFirstMedia('attachments')?->file_name)->toBe('brief.pdf')
        ->and($task->getFirstMedia('attachments')?->getCustomProperty('uploaded_by_user_id'))->toBe($author->user->id);
});

test('creating a task can save checklist subtasks and working files together', function () {
    Storage::fake('public');

    $manager = employeeWith(Ability::AccessTasks, Ability::ManageTasks, Ability::AssignTasks, Ability::ViewAllTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Full create task',
            'type' => 'design',
            'priority' => 'high',
            'assigned_employee_id' => $assignee->id,
            'checklist' => [
                ['title' => 'Check spelling'],
            ],
            'subtasks' => [
                ['title' => 'Draft assets'],
            ],
            'files' => [
                UploadedFile::fake()->image('reference.jpg'),
            ],
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->status)->toBe(TaskStatus::Assigned)
        ->and($task->assigned_employee_id)->toBe($assignee->id)
        ->and($task->checklistItems)->toHaveCount(1)
        ->and($task->subtasks)->toHaveCount(1)
        ->and($task->getMedia('attachments'))->toHaveCount(1);
});

test('a user without structure permissions cannot create checklist items during task creation', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Blocked checklist',
            'type' => 'design',
            'priority' => 'normal',
            'checklist' => [
                ['title' => 'Should not save'],
            ],
        ])
        ->assertForbidden();
});
