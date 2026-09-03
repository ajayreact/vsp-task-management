<?php

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

test('web middleware does not preload vite assets via link headers', function () {
    $middleware = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'];

    expect($middleware)->not->toContain(AddLinkHeadersForPreloadedAssets::class);
});

test('task show full document load succeeds without link preload headers', function () {
    if (! is_file(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Production Vite manifest is required for full document load tests.');
    }

    $task = Task::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $response = $this->actingAs(superAdmin())
            ->get("/tasks/{$task->id}");

        $response->assertOk();
        expect($response->headers->get('Link'))->toBeNull();
    }
});

test('dynamic inertia pages support direct document loads', function () {
    if (! is_file(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Production Vite manifest is required for full document load tests.');
    }

    $project = Project::factory()->create();
    $employee = Employee::factory()->create();

    $this->actingAs(superAdmin())
        ->get("/tasks/projects/{$project->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('TaskManagement/projects/show'));

    $this->actingAs(superAdmin())
        ->get("/admin/employees/{$employee->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Core/admin/employees/edit'));

    $this->actingAs(superAdmin())
        ->get('/tasks')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('TaskManagement/tasks/index'));
});
