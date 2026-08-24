<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();
});

function taskShowFailureRoute(Task $task): void
{
    Route::middleware(['web', 'auth', 'internal'])
        ->get('/_test/task-show-failure/{task}', function () {
            throw new RuntimeException('Simulated task show failure');
        });
}

test('a missing task show page returns a friendly not found error page', function () {
    $this->actingAs(superAdmin())
        ->get('/tasks/999999')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 404)
            ->where('title', 'Page Not Found')
            ->where('message', 'The page you requested could not be found.'));
});

test('an unauthorized task show page returns a friendly access denied error page', function () {
    $task = Task::factory()->create();

    $this->actingAs(employeeWith(Ability::AccessTasks)->user)
        ->get("/tasks/{$task->id}")
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 403)
            ->where('title', 'Access Denied')
            ->where('message', 'You do not have permission to view this page.'));
});

test('a page expired response returns a friendly error page with refresh guidance', function () {
    Route::middleware(['web', 'auth', 'internal'])
        ->get('/_test/page-expired', function () {
            abort(419);
        });

    $this->actingAs(superAdmin())
        ->get('/_test/page-expired')
        ->assertStatus(419)
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 419)
            ->where('title', 'Page Expired')
            ->where('message', 'Your session expired or the page is out of date.')
            ->where('action', 'refresh'));
});

test('hard refresh on task show repeatedly returns the task page without error responses', function () {
    $task = Task::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $this->actingAs(superAdmin())
            ->get("/tasks/{$task->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('TaskManagement/tasks/show'));
    }
});

test('unexpected task show failures are logged and return a friendly server error page', function () {
    $task = Task::factory()->create();
    taskShowFailureRoute($task);

    Log::spy();

    $this->actingAs(superAdmin())
        ->get("/_test/task-show-failure/{$task->id}")
        ->assertStatus(500)
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 500)
            ->where('title', 'Something Went Wrong')
            ->where('message', 'Something went wrong while loading this page.'));

    Log::shouldHaveReceived('error')
        ->with('application.unexpected_failure', \Mockery::on(function (array $context) use ($task) {
            return str_contains($context['url'] ?? '', "/_test/task-show-failure/{$task->id}")
                && ($context['exception_message'] ?? '') === 'Simulated task show failure';
        }));
});

test('ajax requests receive consistent json error payloads', function () {
    $this->actingAs(superAdmin())
        ->getJson('/tasks/999999')
        ->assertNotFound()
        ->assertJson([
            'error' => true,
            'status' => 404,
            'title' => 'Page Not Found',
            'message' => 'The page you requested could not be found.',
        ]);
});
