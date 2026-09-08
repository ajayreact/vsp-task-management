<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskChecklistItem;
use App\Modules\TaskManagement\Support\CreativeChecklistDefaults;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function commonChecklistTitles(): array
{
    return array_column(CreativeChecklistDefaults::common(), 'title');
}

function videoChecklistTitles(): array
{
    return array_column(CreativeChecklistDefaults::video(), 'title');
}

test('poster creative tasks receive the three common checklist items', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Teachers Day Post for VSP Law Associates',
            'type' => 'design',
            'creative_type' => ContentCalendarType::Poster->value,
            'priority' => 'normal',
        ])
        ->assertRedirect();

    $task = Task::query()->sole();
    $titles = $task->checklistItems()->orderBy('sort_order')->pluck('title')->all();

    expect($task->creative_type)->toBe(ContentCalendarType::Poster)
        ->and($titles)->toBe(commonChecklistTitles())
        ->and($task->checklistItems()->where('source', 'system')->where('is_mandatory', true)->count())->toBe(3);
});

test('reel creative tasks receive the three common checklist items', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Festival Reel',
            'type' => 'design',
            'creative_type' => ContentCalendarType::Reel->value,
            'priority' => 'normal',
        ])
        ->assertRedirect();

    $titles = Task::query()->sole()->checklistItems()->orderBy('sort_order')->pluck('title')->all();

    expect($titles)->toBe(commonChecklistTitles());
});

test('video creative tasks receive common and video checklist items', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'VSP Techverse Promotional Video',
            'type' => 'video',
            'creative_type' => ContentCalendarType::Video->value,
            'priority' => 'normal',
        ])
        ->assertRedirect();

    $titles = Task::query()->sole()->checklistItems()->orderBy('sort_order')->pluck('title')->all();

    expect($titles)->toBe(array_merge(commonChecklistTitles(), videoChecklistTitles()))
        ->and($titles)->toHaveCount(6);
});

test('other creative types do not receive default checklists automatically', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    foreach ([ContentCalendarType::Carousel, ContentCalendarType::Story, ContentCalendarType::Article, null] as $creativeType) {
        $this->actingAs($author->user)
            ->post('/tasks', [
                'tm_project_id' => $project->id,
                'title' => 'Other format '.($creativeType?->value ?? 'none'),
                'type' => 'design',
                'creative_type' => $creativeType?->value,
                'priority' => 'normal',
            ])
            ->assertRedirect();
    }

    expect(TaskChecklistItem::query()->count())->toBe(0);
});

test('default checklist items can be completed individually and completion persists', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'creative_type' => ContentCalendarType::Poster,
    ]);

    app(\App\Modules\TaskManagement\Services\CreativeChecklistSyncService::class)->syncDefaults($task);

    $item = $task->checklistItems()->orderBy('sort_order')->orderBy('id')->firstOrFail();

    $this->actingAs($employee->user)
        ->patch("/tasks/{$task->id}/checklist-items/{$item->id}/toggle")
        ->assertRedirect();

    $item->refresh();

    expect($item->is_completed)->toBeTrue()
        ->and($item->completed_by_user_id)->toBe($employee->user->id)
        ->and($item->completed_at)->not->toBeNull();

    $this->actingAs($employee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('checklist.completed', 1)
            ->where('checklist.total', 3)
            ->has('checklist.items', 3)
            ->where('checklist.items', fn ($items) => collect($items)->contains(
                fn ($row) => (int) $row['id'] === $item->id && $row['is_completed'] === true && $row['title'] === $item->title
            )));
});

test('opening a creative task does not duplicate default checklist items', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks, Ability::ViewAllTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Poster once',
            'type' => 'design',
            'creative_type' => ContentCalendarType::Poster->value,
            'priority' => 'normal',
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    $this->actingAs($author->user)->get("/tasks/{$task->id}")->assertOk();
    $this->actingAs($author->user)->get("/tasks/{$task->id}")->assertOk();

    expect($task->checklistItems()->count())->toBe(3)
        ->and($task->checklistItems()->pluck('template_key')->unique()->count())->toBe(3);
});

test('ready status is blocked when required checklist items are incomplete', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'creative_type' => ContentCalendarType::Poster,
        'status' => TaskStatus::InProgress,
    ]);

    app(\App\Modules\TaskManagement\Services\CreativeChecklistSyncService::class)->syncDefaults($task);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/status", ['status' => TaskStatus::InReview->value])
        ->assertRedirect()
        ->assertSessionHas('error', 'Please complete all required checklist items before marking this creative as Ready.');

    expect($task->fresh()->status)->toBe(TaskStatus::InProgress);

    $file = UploadedFile::fake()->image('poster.jpg');

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [$file],
            'notes' => 'Ready for review',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Please complete all required checklist items before marking this creative as Ready.');

    expect($task->fresh()->status)->toBe(TaskStatus::InProgress)
        ->and($task->deliverables()->count())->toBe(0);
});

test('ready status works when all required checklist items are complete', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'creative_type' => ContentCalendarType::Poster,
        'status' => TaskStatus::InProgress,
    ]);

    app(\App\Modules\TaskManagement\Services\CreativeChecklistSyncService::class)->syncDefaults($task);

    $task->checklistItems()->update([
        'is_completed' => true,
        'completed_by_user_id' => $employee->user->id,
        'completed_at' => now(),
    ]);

    $file = UploadedFile::fake()->image('poster.jpg');

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [$file],
            'notes' => 'All checks done',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($task->fresh()->status)->toBe(TaskStatus::InReview)
        ->and($task->deliverables()->count())->toBe(1);
});

test('custom checklist items continue to work alongside defaults', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageTasks, Ability::ViewAllTasks);
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Poster with custom checks',
            'type' => 'design',
            'creative_type' => ContentCalendarType::Poster->value,
            'priority' => 'normal',
            'checklist' => [
                ['title' => 'Client specifically requested blue background'],
                ['title' => 'Verify website URL'],
            ],
        ])
        ->assertRedirect();

    $task = Task::query()->sole();
    $titles = $task->checklistItems()->orderBy('sort_order')->pluck('title')->all();

    expect($titles)->toContain('Client specifically requested blue background')
        ->and($titles)->toContain('Verify website URL')
        ->and($titles)->toContain('Check the Content Spellings')
        ->and($task->checklistItems()->where('source', 'custom')->count())->toBe(2)
        ->and($task->checklistItems()->where('source', 'system')->count())->toBe(3);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/checklist-items", ['title' => 'Check spelling of client name'])
        ->assertRedirect();

    expect($task->checklistItems()->where('title', 'Check spelling of client name')->where('source', 'custom')->exists())->toBeTrue();
});

test('changing creative type adds newly required items without duplicating existing ones', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks, Ability::ViewAllTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Starts as poster',
            'type' => 'design',
            'creative_type' => ContentCalendarType::Poster->value,
            'priority' => 'normal',
        ])
        ->assertRedirect();

    $task = Task::query()->sole();
    $firstCommon = $task->checklistItems()->where('template_key', 'quality.content_spellings')->firstOrFail();
    $firstCommon->update([
        'is_completed' => true,
        'completed_by_user_id' => $author->user->id,
        'completed_at' => now(),
    ]);

    $this->actingAs($author->user)
        ->put("/tasks/{$task->id}", [
            'tm_project_id' => $task->tm_project_id,
            'title' => $task->title,
            'type' => $task->type->value,
            'creative_type' => ContentCalendarType::Video->value,
            'priority' => $task->priority->value,
            'requirement' => $task->requirement,
            'description' => $task->description,
            'department_id' => $task->department_id,
            'estimated_hours' => $task->estimated_hours,
            'due_at' => $task->due_at?->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect();

    $task->refresh();
    $titles = $task->checklistItems()->orderBy('sort_order')->pluck('title')->all();
    $firstCommon->refresh();

    expect($task->creative_type)->toBe(ContentCalendarType::Video)
        ->and($titles)->toBe(array_merge(commonChecklistTitles(), videoChecklistTitles()))
        ->and($task->checklistItems()->count())->toBe(6)
        ->and($firstCommon->is_completed)->toBeTrue()
        ->and($task->checklistItems()->where('template_key', 'quality.content_spellings')->count())->toBe(1);

    $this->actingAs($author->user)
        ->put("/tasks/{$task->id}", [
            'tm_project_id' => $task->tm_project_id,
            'title' => $task->title,
            'type' => $task->type->value,
            'creative_type' => ContentCalendarType::Poster->value,
            'priority' => $task->priority->value,
            'requirement' => $task->requirement,
            'description' => $task->description,
            'department_id' => $task->department_id,
            'estimated_hours' => $task->estimated_hours,
            'due_at' => $task->due_at?->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect();

    $task->refresh();

    expect($task->checklistItems()->count())->toBe(6)
        ->and($task->checklistItems()->where('template_key', 'video.watch_twice')->exists())->toBeTrue()
        ->and($firstCommon->fresh()->is_completed)->toBeTrue();
});
