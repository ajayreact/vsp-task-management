<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\RecruiterActivityType;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\TaskManagement\Models\Task;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function activityStaff(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

function activityFor(Employee $employee, array $attributes = []): RecruiterDailyActivity
{
    return RecruiterDailyActivity::factory()->forEmployee($employee)->create($attributes);
}

function activityPayload(array $overrides = []): array
{
    return [
        'activity_date' => today()->toDateString(),
        'activity_type' => 'linkedin_sourcing',
        'title' => 'LinkedIn sourcing for STEM OPT data analysts',
        'description' => 'Contacted 25 potential STEM OPT candidates through LinkedIn.',
        'start_time' => '',
        'end_time' => '',
        'quantity' => '',
        'recruiter_task_id' => '',
        'remarks' => '',
        ...$overrides,
    ];
}

/**
 * @return list<string>
 */
function exportedCells(TestResponse $response): array
{
    $path = tempnam(sys_get_temp_dir(), 'ro-activities').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    try {
        return collect(IOFactory::load($path)->getActiveSheet()->toArray())
            ->flatten()
            ->filter(fn ($cell) => $cell !== null && $cell !== '')
            ->map(fn ($cell) => (string) $cell)
            ->values()
            ->all();
    } finally {
        @unlink($path);
    }
}

// A. Creation

test('a recruiter records an activity for themselves', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $today = today()->toDateString();

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['quantity' => 25]))
        ->assertSessionHasNoErrors()
        ->assertRedirect("/recruiter/activities?from={$today}&to={$today}");

    $activity = RecruiterDailyActivity::query()->sole();

    expect($activity->employee_id)->toBe($recruiter->id)
        ->and($activity->activity_date->toDateString())->toBe($today)
        ->and($activity->activity_type)->toBe(RecruiterActivityType::LinkedinSourcing)
        ->and($activity->quantity)->toBe(25)
        ->and($activity->duration_minutes)->toBeNull()
        ->and($activity->recruiter_task_id)->toBeNull()
        ->and($activity->created_by_user_id)->toBe($recruiter->user->id)
        ->and($activity->updated_by_user_id)->toBe($recruiter->user->id);
});

test('the create page defaults to today and lists only the recruiter\'s own linkable tasks', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $colleague = activityStaff(SystemRole::Recruiter);
    $own = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->create();
    $cancelled = RecruiterTask::factory()->cancelled()->assignedTo($recruiter)->create();
    $theirs = RecruiterTask::factory()->assignedTo($colleague)->create();

    $this->actingAs($recruiter->user)
        ->get("/recruiter/activities/create?task={$own->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/activities/create')
            ->where('defaults.activity_date', today()->toDateString())
            ->where('defaults.recruiter_task_id', (string) $own->id)
            ->where('minDate', today()->subDays(7)->toDateString())
            ->where('maxDate', today()->toDateString())
            ->has('activityTypes', 12)
            ->where('tasks', fn ($tasks) => collect($tasks)->pluck('id')->all() === [$own->id]));

    $this->actingAs($recruiter->user)
        ->get("/recruiter/activities/create?task={$theirs->id}")
        ->assertInertia(fn ($page) => $page->where('defaults.recruiter_task_id', ''));

    expect($cancelled->id)->not->toBe($own->id);
});

test('required fields are validated', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', [])
        ->assertSessionHasErrors(['activity_date', 'activity_type', 'title']);

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
});

test('the activity type must be one of the known types', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['activity_type' => 'candidate_placement']))
        ->assertSessionHasErrors('activity_type');
});

test('the activity date must be a real, non-future date', function (string $date) {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['activity_date' => $date]))
        ->assertSessionHasErrors('activity_date');

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
})->with([
    'not a date' => 'yesterday-ish',
    'wrong format' => '29/09/2026',
    'tomorrow' => fn () => today()->addDay()->toDateString(),
]);

test('the quantity must be a whole number from zero up', function (mixed $quantity) {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['quantity' => $quantity]))
        ->assertSessionHasErrors('quantity');
})->with([-1, 'many', 2.5, 100001]);

// B. Ownership

test('a recruiter cannot record an activity for another employee', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $colleague = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['employee_id' => $colleague->id]))
        ->assertSessionHasNoErrors();

    expect(RecruiterDailyActivity::query()->sole()->employee_id)->toBe($recruiter->id)
        ->and(RecruiterDailyActivity::query()->forEmployee($colleague)->count())->toBe(0);
});

test('a recruiter cannot view, edit or delete another recruiter\'s activity', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $colleague = activityStaff(SystemRole::Recruiter);
    $theirs = activityFor($colleague, ['title' => 'Their activity']);

    $this->actingAs($recruiter->user)->get("/recruiter/activities/{$theirs->id}")->assertForbidden();
    $this->actingAs($recruiter->user)->get("/recruiter/activities/{$theirs->id}/edit")->assertForbidden();
    $this->actingAs($recruiter->user)->put("/recruiter/activities/{$theirs->id}", activityPayload(['title' => 'Hijacked']))->assertForbidden();
    $this->actingAs($recruiter->user)->put("/recruiter/activities/{$theirs->id}", [])->assertForbidden();
    $this->actingAs($recruiter->user)->delete("/recruiter/activities/{$theirs->id}")->assertForbidden();

    expect($theirs->fresh()->title)->toBe('Their activity');
});

// C. Date rules

test('a recruiter may record today and each of the previous seven days', function (int $daysAgo) {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['activity_date' => today()->subDays($daysAgo)->toDateString()]))
        ->assertSessionHasNoErrors();

    expect(RecruiterDailyActivity::query()->sole()->activity_date->toDateString())->toBe(today()->subDays($daysAgo)->toDateString());
})->with([0, 1, 7]);

test('a recruiter cannot record an activity more than seven days back', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['activity_date' => today()->subDays(8)->toDateString()]))
        ->assertSessionHasErrors('activity_date');

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
});

test('a recruiter cannot move their activity outside the window or change an old one', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $recent = activityFor($recruiter, ['activity_date' => today()->subDays(2)]);
    $old = activityFor($recruiter, ['activity_date' => today()->subDays(10), 'title' => 'Old entry']);

    $this->actingAs($recruiter->user)
        ->put("/recruiter/activities/{$recent->id}", activityPayload(['activity_date' => today()->subDays(9)->toDateString()]))
        ->assertSessionHasErrors('activity_date');

    $this->actingAs($recruiter->user)->get("/recruiter/activities/{$old->id}")->assertOk()
        ->assertInertia(fn ($page) => $page->where('activity.can.update', false)->where('activity.can.delete', false));
    $this->actingAs($recruiter->user)->get("/recruiter/activities/{$old->id}/edit")->assertForbidden();
    $this->actingAs($recruiter->user)->put("/recruiter/activities/{$old->id}", activityPayload())->assertForbidden();
    $this->actingAs($recruiter->user)->delete("/recruiter/activities/{$old->id}")->assertForbidden();

    expect($recent->fresh()->activity_date->toDateString())->toBe(today()->subDays(2)->toDateString())
        ->and($old->fresh()->title)->toBe('Old entry');
});

test('a recruiter lead views and corrects team activities from any past date', function () {
    $lead = activityStaff(SystemRole::RecruiterLead);
    $recruiter = activityStaff(SystemRole::Recruiter);
    $old = activityFor($recruiter, ['activity_date' => today()->subDays(30), 'title' => 'Month-old sourcing']);
    $from = today()->subDays(40)->toDateString();
    $to = today()->toDateString();

    $this->actingAs($lead->user)
        ->get("/recruiter/activities?from={$from}&to={$to}")
        ->assertInertia(fn ($page) => $page
            ->where('can.viewTeam', true)
            ->has('activities.data', 1)
            ->where('activities.data.0.title', 'Month-old sourcing'));

    $this->actingAs($lead->user)->get("/recruiter/activities/{$old->id}")->assertOk();
    $this->actingAs($lead->user)
        ->get("/recruiter/activities/{$old->id}/edit")
        ->assertInertia(fn ($page) => $page->where('minDate', null)->where('activity.is_own', false));

    $this->actingAs($lead->user)
        ->put("/recruiter/activities/{$old->id}", activityPayload([
            'activity_date' => today()->subDays(31)->toDateString(),
            'title' => 'Corrected by lead',
        ]))
        ->assertRedirect("/recruiter/activities/{$old->id}");

    $old->refresh();

    expect($old->title)->toBe('Corrected by lead')
        ->and($old->employee_id)->toBe($recruiter->id)
        ->and($old->updated_by_user_id)->toBe($lead->user->id)
        ->and($old->activity_date->toDateString())->toBe(today()->subDays(31)->toDateString());
});

test('the list defaults to today', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    activityFor($recruiter, ['title' => 'Today work']);
    RecruiterDailyActivity::factory()->forEmployee($recruiter)->yesterday()->create(['title' => 'Yesterday work']);

    $this->actingAs($recruiter->user)
        ->get('/recruiter/activities')
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/activities/index')
            ->where('filters.from', today()->toDateString())
            ->where('filters.to', today()->toDateString())
            ->has('activities.data', 1)
            ->where('activities.data.0.title', 'Today work'));
});

// D. Duration

test('start and end times are optional', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload([
            'activity_type' => 'university_research',
            'title' => 'University career-center research',
            'description' => 'Researched 8 university career centers.',
            'quantity' => 8,
        ]))
        ->assertSessionHasNoErrors();

    $activity = RecruiterDailyActivity::query()->sole();

    expect($activity->start_time)->toBeNull()
        ->and($activity->end_time)->toBeNull()
        ->and($activity->duration_minutes)->toBeNull();
});

test('the duration is calculated on the server and ignores any duration sent by the browser', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload([
            'start_time' => '09:15',
            'end_time' => '11:45',
            'duration_minutes' => 999,
        ]))
        ->assertSessionHasNoErrors();

    $activity = RecruiterDailyActivity::query()->sole();

    expect($activity->duration_minutes)->toBe(150)
        ->and(RecruiterDailyActivity::clock($activity->start_time))->toBe('09:15')
        ->and(RecruiterDailyActivity::clock($activity->end_time))->toBe('11:45');
});

test('the end time must come after the start time', function (string $start, string $end) {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['start_time' => $start, 'end_time' => $end]))
        ->assertSessionHasErrors('end_time');

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
})->with([
    'end before start' => ['11:00', '10:00'],
    'same time' => ['10:00', '10:00'],
    'overnight' => ['23:00', '01:00'],
]);

test('start and end times come as a pair', function (array $times, string $field) {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload($times))
        ->assertSessionHasErrors($field);
})->with([
    'start only' => [['start_time' => '09:00'], 'end_time'],
    'end only' => [['end_time' => '10:00'], 'start_time'],
]);

test('times outside a single day are rejected so a duration can never pass 24 hours', function (array $times) {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload([...$times, 'duration_minutes' => 3000]))
        ->assertSessionHasErrors();

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
})->with([
    'hour past midnight' => [['start_time' => '00:00', 'end_time' => '25:00']],
    'with seconds' => [['start_time' => '00:00:00', 'end_time' => '23:59:59']],
]);

test('the longest possible activity is just under 24 hours', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['start_time' => '00:00', 'end_time' => '23:59', 'duration_minutes' => 5000]))
        ->assertSessionHasNoErrors();

    expect(RecruiterDailyActivity::query()->sole()->duration_minutes)->toBe(1439);
});

test('editing recalculates the duration and clearing the times clears it', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $activity = RecruiterDailyActivity::factory()->forEmployee($recruiter)->withDuration('09:00', '10:00')->create();

    $this->actingAs($recruiter->user)
        ->put("/recruiter/activities/{$activity->id}", activityPayload(['start_time' => '13:00', 'end_time' => '13:45']))
        ->assertSessionHasNoErrors();
    expect($activity->fresh()->duration_minutes)->toBe(45);

    $this->actingAs($recruiter->user)
        ->put("/recruiter/activities/{$activity->id}", activityPayload())
        ->assertSessionHasNoErrors();
    expect($activity->fresh()->duration_minutes)->toBeNull()
        ->and($activity->fresh()->start_time)->toBeNull();
});

// E. Quantity

test('the quantity is optional and zero is allowed', function (mixed $quantity, ?int $stored) {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['quantity' => $quantity]))
        ->assertSessionHasNoErrors();

    expect(RecruiterDailyActivity::query()->sole()->quantity)->toBe($stored);
})->with([
    'left blank' => ['', null],
    'zero' => [0, 0],
    'thirty' => ['30', 30],
]);

test('a negative quantity is rejected', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['quantity' => -5]))
        ->assertSessionHasErrors('quantity');

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
});

// F. Task linking

test('an activity can be linked to one of the recruiter\'s own tasks and shows on that task', function () {
    $lead = activityStaff(SystemRole::RecruiterLead);
    $recruiter = activityStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->createdBy($lead->user)->create(['target_count' => 30]);

    $this->actingAs($recruiter->user)->post('/recruiter/activities', activityPayload([
        'title' => 'LinkedIn sourcing',
        'start_time' => '09:30',
        'end_time' => '10:30',
        'quantity' => 15,
        'recruiter_task_id' => $task->id,
    ]))->assertSessionHasNoErrors();
    $this->actingAs($recruiter->user)->post('/recruiter/activities', activityPayload([
        'activity_type' => 'candidate_outreach',
        'title' => 'Candidate outreach',
        'start_time' => '11:00',
        'end_time' => '12:00',
        'quantity' => 10,
        'recruiter_task_id' => $task->id,
    ]))->assertSessionHasNoErrors();

    expect(RecruiterDailyActivity::query()->forTask($task)->count())->toBe(2);

    foreach ([$recruiter->user, $lead->user] as $viewer) {
        $this->actingAs($viewer)
            ->get("/recruiter/tasks/{$task->id}")
            ->assertInertia(fn ($page) => $page
                ->has('dailyActivities.items', 2)
                ->where('dailyActivities.items.0.title', 'LinkedIn sourcing')
                ->where('dailyActivities.items.1.quantity', 10)
                ->where('dailyActivities.reported_quantity', 25)
                ->where('task.target_count', 30)
                ->missing('dailyActivities.percentage'));
    }
});

test('an activity can exist without a task', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)->post('/recruiter/activities', activityPayload())->assertSessionHasNoErrors();

    expect(RecruiterDailyActivity::query()->sole()->recruiter_task_id)->toBeNull();
});

test('a recruiter cannot link another recruiter\'s task or a cancelled task', function (string $case) {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $task = $case === 'another recruiter\'s task'
        ? RecruiterTask::factory()->inProgress()->assignedTo(activityStaff(SystemRole::Recruiter))->create()
        : RecruiterTask::factory()->cancelled()->assignedTo($recruiter)->create();

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['recruiter_task_id' => $task->id]))
        ->assertSessionHasErrors('recruiter_task_id');

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
})->with(['another recruiter\'s task', 'a cancelled task']);

test('a digital marketing task cannot be linked', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $marketing = Task::factory()->create(['assigned_employee_id' => $recruiter->id]);

    expect(RecruiterTask::query()->whereKey($marketing->id)->where('assigned_employee_id', $recruiter->id)->exists())->toBeFalse();

    $this->actingAs($recruiter->user)
        ->post('/recruiter/activities', activityPayload(['recruiter_task_id' => $marketing->id]))
        ->assertSessionHasErrors('recruiter_task_id');

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
});

test('cancelling a task keeps its activities linked', function () {
    $lead = activityStaff(SystemRole::RecruiterLead);
    $recruiter = activityStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->createdBy($lead->user)->create();
    $activity = RecruiterDailyActivity::factory()->forEmployee($recruiter)->withTask($task)->create();

    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/cancel", ['reason' => 'Closed'])->assertSessionHas('success');

    expect($activity->fresh()->recruiter_task_id)->toBe($task->id);

    $this->actingAs($recruiter->user)
        ->put("/recruiter/activities/{$activity->id}", activityPayload(['recruiter_task_id' => $task->id, 'title' => 'Still linked']))
        ->assertSessionHasNoErrors();

    expect($activity->fresh()->title)->toBe('Still linked')
        ->and($activity->fresh()->recruiter_task_id)->toBe($task->id);
});

test('deleting a task leaves its activities in place, unlinked', function () {
    $lead = activityStaff(SystemRole::RecruiterLead);
    $recruiter = activityStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->assignedTo($recruiter)->createdBy($lead->user)->create();
    $activity = RecruiterDailyActivity::factory()->forEmployee($recruiter)->withTask($task)->create();

    $this->actingAs($lead->user)->delete("/recruiter/tasks/{$task->id}")->assertRedirect('/recruiter/tasks');

    expect(RecruiterTask::query()->count())->toBe(0)
        ->and($activity->fresh())->not->toBeNull()
        ->and($activity->fresh()->recruiter_task_id)->toBeNull();
});

test('task workflow moves never create daily activities', function () {
    $lead = activityStaff(SystemRole::RecruiterLead);
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($lead->user)->post('/recruiter/tasks', [
        'title' => 'Source STEM OPT candidates',
        'work_type' => 'candidate_sourcing',
        'priority' => 'normal',
        'assigned_employee_id' => $recruiter->id,
    ])->assertSessionHasNoErrors();
    $task = RecruiterTask::query()->sole();

    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/accept");
    $this->actingAs($recruiter->user)->post("/recruiter/tasks/{$task->id}/complete", ['achieved_count' => 5]);
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/reopen", ['reason' => 'More needed']);
    $this->actingAs($lead->user)->post("/recruiter/tasks/{$task->id}/cancel", ['reason' => 'Closed']);

    expect($task->fresh()->status->value)->toBe('cancelled')
        ->and(RecruiterDailyActivity::query()->count())->toBe(0);
});

test('a lead correcting an activity may only link the owner\'s tasks', function () {
    $lead = activityStaff(SystemRole::RecruiterLead);
    $recruiter = activityStaff(SystemRole::Recruiter);
    $activity = activityFor($recruiter);
    $leadsOwnTask = RecruiterTask::factory()->inProgress()->assignedTo($lead)->create();
    $recruitersTask = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->create();

    $this->actingAs($lead->user)
        ->get("/recruiter/activities/{$activity->id}/edit")
        ->assertInertia(fn ($page) => $page->where('tasks', fn ($tasks) => collect($tasks)->pluck('id')->all() === [$recruitersTask->id]));

    $this->actingAs($lead->user)
        ->put("/recruiter/activities/{$activity->id}", activityPayload(['recruiter_task_id' => $leadsOwnTask->id]))
        ->assertSessionHasErrors('recruiter_task_id');
});

// G. Authorization

test('a recruiter sees only their own activities', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $colleague = activityStaff(SystemRole::Recruiter);
    activityFor($recruiter, ['title' => 'Mine']);
    activityFor($colleague, ['title' => 'Theirs']);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/activities?recruiter={$colleague->id}")
        ->assertInertia(fn ($page) => $page
            ->where('pageTitle', 'My Daily Activities')
            ->where('can.viewTeam', false)
            ->where('filters.recruiter', null)
            ->where('recruiters', [])
            ->has('activities.data', 1)
            ->where('activities.data.0.title', 'Mine'));
});

test('a recruiter lead sees team activities and can filter by recruiter, type and task', function () {
    $lead = activityStaff(SystemRole::RecruiterLead);
    $first = activityStaff(SystemRole::Recruiter);
    $second = activityStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($second)->create();
    activityFor($first, ['title' => 'First sourcing']);
    activityFor($second, ['title' => 'Second calls', 'activity_type' => RecruiterActivityType::PhoneCalls, 'recruiter_task_id' => $task->id]);

    $this->actingAs($lead->user)
        ->get('/recruiter/activities')
        ->assertInertia(fn ($page) => $page
            ->where('pageTitle', 'Daily Activities')
            ->has('activities.data', 2)
            ->where('tasks', [['id' => $task->id, 'label' => $task->title]]));

    foreach (["recruiter={$second->id}", 'type=phone_calls', "task={$task->id}"] as $filter) {
        $this->actingAs($lead->user)
            ->get("/recruiter/activities?{$filter}")
            ->assertInertia(fn ($page) => $page
                ->has('activities.data', 1)
                ->where('activities.data.0.title', 'Second calls')
                ->where('activities.data.0.recruiter_name', $second->user->name));
    }
});

test('staff without recruiter access are refused', function (string $method, string $url) {
    $marketer = activityStaff(SystemRole::Employee);
    $activity = activityFor(activityStaff(SystemRole::Recruiter));
    $url = str_replace('{id}', (string) $activity->id, $url);

    $this->actingAs($marketer->user)->{$method}($url, $method === 'get' ? [] : activityPayload())->assertForbidden();
})->with([
    ['get', '/recruiter/activities'],
    ['get', '/recruiter/activities/create'],
    ['post', '/recruiter/activities'],
    ['get', '/recruiter/activities/{id}'],
    ['put', '/recruiter/activities/{id}'],
    ['delete', '/recruiter/activities/{id}'],
    ['get', '/recruiter/activities/export/excel'],
]);

test('guests are sent to login', function () {
    $this->get('/recruiter/activities')->assertRedirect('/login');
});

test('super admin without an employee profile can review the team log but cannot record an activity', function () {
    $admin = superAdmin();
    activityFor(activityStaff(SystemRole::Recruiter));

    $this->actingAs($admin)->get('/recruiter/activities')->assertOk()
        ->assertInertia(fn ($page) => $page->has('activities.data', 1));
    $this->actingAs($admin)->get('/recruiter/activities/create')->assertForbidden();
    $this->actingAs($admin)->post('/recruiter/activities', activityPayload())->assertForbidden();

    expect(RecruiterDailyActivity::query()->count())->toBe(1);
});

test('an activity recorded by super admin belongs to super admin, never to a recruiter', function () {
    $admin = superAdminEmployee();
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($admin->user)
        ->post('/recruiter/activities', activityPayload(['employee_id' => $recruiter->id]))
        ->assertSessionHasNoErrors();

    expect(RecruiterDailyActivity::query()->sole()->employee_id)->toBe($admin->id);
});

// H. Editing and deleting

test('a recruiter edits their own activity', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $activity = activityFor($recruiter, ['title' => 'Before']);

    $this->actingAs($recruiter->user)
        ->get("/recruiter/activities/{$activity->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/activities/edit')
            ->where('activity.title', 'Before')
            ->where('activity.is_own', true));

    $this->actingAs($recruiter->user)
        ->put("/recruiter/activities/{$activity->id}", activityPayload(['title' => 'After', 'activity_type' => 'phone_calls', 'quantity' => 12]))
        ->assertRedirect("/recruiter/activities/{$activity->id}");

    $activity->refresh();

    expect($activity->title)->toBe('After')
        ->and($activity->activity_type)->toBe(RecruiterActivityType::PhoneCalls)
        ->and($activity->quantity)->toBe(12)
        ->and($activity->updated_by_user_id)->toBe($recruiter->user->id);
});

test('a recruiter deletes their own activity', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $activity = RecruiterDailyActivity::factory()->forEmployee($recruiter)->yesterday()->create();
    $date = $activity->activity_date->toDateString();

    $this->actingAs($recruiter->user)
        ->delete("/recruiter/activities/{$activity->id}")
        ->assertRedirect("/recruiter/activities?from={$date}&to={$date}");

    expect(RecruiterDailyActivity::query()->count())->toBe(0);
});

test('what the page offers matches what the server allows', function () {
    $owner = activityStaff(SystemRole::Recruiter);
    $teamViewer = activityStaff(SystemRole::Recruiter);
    $teamViewer->user->givePermissionTo(Ability::ViewRecruiterTeam->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $lead = activityStaff(SystemRole::RecruiterLead);
    $activity = activityFor($owner);

    $cases = [
        [$owner, true],
        [$teamViewer, false],
        [$lead, true],
    ];

    foreach ($cases as [$viewer, $allowed]) {
        $this->actingAs($viewer->user)
            ->get("/recruiter/activities/{$activity->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('activity.can.update', $allowed)
                ->where('activity.can.delete', $allowed));

        $response = $this->actingAs($viewer->user)->put("/recruiter/activities/{$activity->id}", activityPayload(['title' => 'Checked']));

        $allowed ? $response->assertSessionHasNoErrors()->assertRedirect() : $response->assertForbidden();
    }
});

// I. Separation

test('daily activities never appear in digital marketing task management', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    activityFor($recruiter, ['title' => 'Recruiter activity only']);
    Task::factory()->create(['title' => 'Marketing campaign']);

    $this->actingAs(employeeWith(Ability::AccessTasks, Ability::ViewAllTasks)->user)
        ->get('/tasks')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/index')
            ->where('tasks.data', fn ($tasks) => collect($tasks)->pluck('title')->contains('Marketing campaign')
                && ! collect($tasks)->pluck('title')->contains('Recruiter activity only')));

    expect((new RecruiterDailyActivity)->getTable())->toBe('ro_daily_activities');
});

test('digital marketing task managers cannot reach daily activities', function () {
    $this->actingAs(employeeWith(Ability::AccessTasks, Ability::ViewAllTasks, Ability::ManageTasks)->user)
        ->get('/recruiter/activities')
        ->assertForbidden();
});

// J. Dashboard

test('the dashboard shows today\'s activity counts and recent entries', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    $task = RecruiterTask::factory()->inProgress()->assignedTo($recruiter)->create(['title' => 'Source STEM OPT candidates']);
    RecruiterDailyActivity::factory()->forEmployee($recruiter)->withDuration('09:30', '10:30')->withQuantity(15)->withTask($task)->create(['title' => 'LinkedIn sourcing']);
    RecruiterDailyActivity::factory()->forEmployee($recruiter)->withDuration('11:00', '11:45')->withQuantity(10)->create(['title' => 'Candidate outreach']);
    RecruiterDailyActivity::factory()->forEmployee($recruiter)->create(['title' => 'Team huddle']);
    RecruiterDailyActivity::factory()->forEmployee($recruiter)->yesterday()->withQuantity(99)->create(['title' => 'Yesterday']);
    activityFor(activityStaff(SystemRole::Recruiter), ['quantity' => 50, 'title' => 'Someone else']);

    $this->actingAs($recruiter->user)
        ->get('/recruiter')
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/dashboard')
            ->where('todayActivities.count', 3)
            ->where('todayActivities.reported_quantity', 25)
            ->where('todayActivities.recorded_minutes', 105)
            ->has('todayActivities.recent', 3)
            ->where('todayActivities.recent', fn ($recent) => collect($recent)->pluck('title')->sort()->values()->all() === ['Candidate outreach', 'LinkedIn sourcing', 'Team huddle'])
            ->where('todayActivities.recent', fn ($recent) => collect($recent)->firstWhere('title', 'LinkedIn sourcing')['task_title'] === 'Source STEM OPT candidates'));
});

test('the dashboard activity summary carries no score, ranking or comparison', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);

    $this->actingAs($recruiter->user)
        ->get('/recruiter')
        ->assertInertia(fn ($page) => $page
            ->where('todayActivities', fn ($summary) => collect($summary)->keys()->sort()->values()->all() === ['count', 'recent', 'recorded_minutes', 'reported_quantity'])
            ->where('todayActivities.count', 0)
            ->where('todayActivities.reported_quantity', null)
            ->where('todayActivities.recorded_minutes', null));
});

test('the dashboard has no activity summary without an employee profile', function () {
    $this->actingAs(superAdmin())
        ->get('/recruiter')
        ->assertInertia(fn ($page) => $page->where('todayActivities', null));
});

// K. Export

test('a recruiter exports only their own activities', function () {
    $recruiter = activityStaff(SystemRole::Recruiter);
    activityFor($recruiter, ['title' => 'My export row', 'quantity' => 7]);
    activityFor(activityStaff(SystemRole::Recruiter), ['title' => 'Colleague export row']);

    $response = $this->actingAs($recruiter->user)->get('/recruiter/activities/export/excel');
    $response->assertOk();

    $cells = exportedCells($response);

    expect($cells)->toContain('My export row')
        ->and($cells)->toContain('Related task')
        ->and($cells)->not->toContain('Colleague export row');
});

test('a recruiter lead exports team activities within the chosen filters', function () {
    $lead = activityStaff(SystemRole::RecruiterLead);
    $first = activityStaff(SystemRole::Recruiter);
    $second = activityStaff(SystemRole::Recruiter);
    activityFor($first, ['title' => 'First row']);
    activityFor($second, ['title' => 'Second row']);
    RecruiterDailyActivity::factory()->forEmployee($second)->daysAgo(20)->create(['title' => 'Old row']);

    $all = exportedCells($this->actingAs($lead->user)->get('/recruiter/activities/export/excel'));
    expect($all)->toContain('First row')->toContain('Second row')->not->toContain('Old row');

    $filtered = exportedCells($this->actingAs($lead->user)->get("/recruiter/activities/export/excel?recruiter={$second->id}&from=".today()->subDays(30)->toDateString()));
    expect($filtered)->toContain('Second row')->toContain('Old row')->not->toContain('First row');
});
