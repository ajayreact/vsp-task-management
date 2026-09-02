<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Services\ContentCalendarItemShareLinkService;
use App\Modules\TaskManagement\Services\ContentCalendarScheduleShareLinkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

function crtCalendarEmployee(): Employee
{
    $department = Department::factory()->create(['code' => 'CRT']);
    $employee = Employee::factory()->for($department)->create();
    $employee->user->syncPermissions([Ability::AccessTasks->value]);

    return $employee;
}

function opsCalendarEmployee(): Employee
{
    $department = Department::factory()->create(['code' => 'OPS']);
    $employee = Employee::factory()->for($department)->create();
    $employee->user->syncPermissions([Ability::AccessTasks->value]);

    return $employee;
}

test('admin can view the client content calendar', function () {
    $company = Company::factory()->create(['name' => 'Pro Logging']);
    ContentCalendarItem::factory()->for($company, 'company')->create([
        'scheduled_date' => now()->startOfMonth()->toDateString(),
    ]);

    $admin = employeeWith(Ability::AccessTasks, Ability::ViewContentCalendar);

    $this->actingAs($admin->user)
        ->get("/tasks/content-calendar?client={$company->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/content-calendar/index')
            ->has('items', 1));
});

test('crt users can view the content calendar', function () {
    $company = Company::factory()->create();

    $this->actingAs(crtCalendarEmployee()->user)
        ->get("/tasks/content-calendar?client={$company->id}")
        ->assertOk();
});

test('ops users can view the content calendar', function () {
    $company = Company::factory()->create();

    $this->actingAs(opsCalendarEmployee()->user)
        ->get("/tasks/content-calendar?client={$company->id}")
        ->assertOk();
});

test('unauthorized users cannot access the content calendar', function () {
    $company = Company::factory()->create();
    $employee = employeeWith(Ability::AccessTasks);
    $employee->department->update(['code' => 'FIN']);

    $this->actingAs($employee->user)
        ->get("/tasks/content-calendar?client={$company->id}")
        ->assertForbidden();
});

test('content calendar items can be created with future dates', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageContentCalendar);

    $futureDate = now()->addMonth()->toDateString();

    $this->actingAs($manager->user)
        ->post('/tasks/content-calendar', [
            'tm_company_id' => $company->id,
            'scheduled_date' => $futureDate,
            'content_type' => ContentCalendarType::Poster->value,
            'platform' => ContentCalendarPlatform::Instagram->value,
            'description' => 'Festival campaign post',
            'status' => ContentCalendarStatus::Draft->value,
            'files' => [UploadedFile::fake()->image('poster.jpg')],
        ])
        ->assertRedirect();

    $item = ContentCalendarItem::query()->sole();

    expect($item->scheduled_date->toDateString())->toBe($futureDate)
        ->and($item->getMedia('attachments'))->toHaveCount(1);
});

test('content calendar items can be edited', function () {
    $item = ContentCalendarItem::factory()->create([
        'description' => 'Old caption',
        'status' => ContentCalendarStatus::Draft,
    ]);

    $manager = employeeWith(Ability::AccessTasks, Ability::ManageContentCalendar);

    $this->actingAs($manager->user)
        ->put("/tasks/content-calendar/{$item->id}", [
            'tm_company_id' => $item->tm_company_id,
            'scheduled_date' => $item->scheduled_date->toDateString(),
            'content_type' => $item->content_type->value,
            'platform' => $item->platform->value,
            'description' => 'Updated caption',
            'status' => ContentCalendarStatus::Ready->value,
        ])
        ->assertRedirect();

    expect($item->fresh()->description)->toBe('Updated caption')
        ->and($item->fresh()->status)->toBe(ContentCalendarStatus::Ready);
});

test('content calendar attachment download returns attachment response', function () {
    Storage::fake('public');

    $item = ContentCalendarItem::factory()->create();
    $media = $item->addMedia(UploadedFile::fake()->image('poster.jpg'))->toMediaCollection('attachments');

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewContentCalendar);

    $this->actingAs($viewer->user)
        ->get("/tasks/content-calendar/{$item->id}/attachments/{$media->uuid}/download")
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('content calendar filters work', function () {
    $company = Company::factory()->create();
    $periodStart = now()->startOfMonth()->toDateString();

    ContentCalendarItem::factory()->for($company, 'company')->create([
        'scheduled_date' => $periodStart,
        'content_type' => ContentCalendarType::Poster,
        'platform' => ContentCalendarPlatform::Instagram,
        'status' => ContentCalendarStatus::Ready,
        'description' => 'Alpha post',
    ]);

    ContentCalendarItem::factory()->for($company, 'company')->create([
        'scheduled_date' => $periodStart,
        'content_type' => ContentCalendarType::Reel,
        'platform' => ContentCalendarPlatform::YouTube,
        'status' => ContentCalendarStatus::Draft,
        'description' => 'Beta post',
    ]);

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewContentCalendar);

    $this->actingAs($viewer->user)
        ->get("/tasks/content-calendar?client={$company->id}&period_start={$periodStart}&content_type=poster&search=Alpha")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('items', 1)->where('items.0.description', 'Alpha post'));
});

test('previous and next 15-day navigation works', function () {
    $company = Company::factory()->create();
    $start = Carbon::parse('2026-09-01');

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewContentCalendar);

    $this->actingAs($viewer->user)
        ->get("/tasks/content-calendar?client={$company->id}&period_start={$start->toDateString()}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('period.start', '2026-09-01')
            ->where('period.end', '2026-09-15')
            ->where('period.previous_start', '2026-08-17')
            ->where('period.next_start', '2026-09-16'));
});

test('individual content share links work publicly', function () {
    Storage::fake('public');

    $item = ContentCalendarItem::factory()->create([
        'description' => 'Public caption only',
    ]);
    $item->addMedia(UploadedFile::fake()->image('post.jpg'))->toMediaCollection('attachments');

    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);

    $this->get($link->publicUrl())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/content-share/show-item')
            ->where('item.description', 'Public caption only')
            ->missing('item.id'));
});

test('15-day schedule share links work publicly', function () {
    $company = Company::factory()->create(['name' => 'Pro Logging']);
    $start = Carbon::parse('2026-09-01');
    $end = $start->copy()->addDays(14);

    ContentCalendarItem::factory()->for($company, 'company')->create([
        'scheduled_date' => $start->toDateString(),
        'description' => 'Schedule item',
    ]);

    $link = app(ContentCalendarScheduleShareLinkService::class)->getOrCreate(
        $company,
        $start,
        $end,
        ContentCalendarItem::factory()->create()->createdBy,
    );

    $this->get($link->publicUrl())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/content-share/show-schedule')
            ->where('client_name', 'Pro Logging')
            ->has('items', 1));
});

test('revoked content share links are rejected', function () {
    $item = ContentCalendarItem::factory()->create();
    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);
    $link->update(['revoked_at' => now()]);

    $this->get($link->publicUrl())->assertNotFound();
});

test('viewing content calendar does not modify historical items', function () {
    $company = Company::factory()->create();
    $item = ContentCalendarItem::factory()->for($company, 'company')->create([
        'description' => 'Stable caption',
        'scheduled_date' => now()->subMonths(2)->toDateString(),
    ]);

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewContentCalendar);

    $this->actingAs($viewer->user)
        ->get("/tasks/content-calendar?client={$company->id}&period_start={$item->scheduled_date->toDateString()}")
        ->assertOk();

    expect($item->fresh()->description)->toBe('Stable caption');
});
