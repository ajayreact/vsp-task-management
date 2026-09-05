<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarTopic;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\Holiday;
use App\Modules\TaskManagement\Services\ContentCalendarItemShareLinkService;
use App\Modules\TaskManagement\Services\ContentCalendarScheduleShareLinkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            ->has('items', 1)
            ->has('kpis'));
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
            'topic' => ContentCalendarTopic::Promotional->value,
            'platforms' => [ContentCalendarPlatform::Instagram->value, ContentCalendarPlatform::Facebook->value],
            'description' => 'Festival campaign post',
            'status' => ContentCalendarStatus::Draft->value,
            'files' => [UploadedFile::fake()->image('poster.jpg')],
        ])
        ->assertRedirect();

    $item = ContentCalendarItem::query()->sole();

    expect($item->scheduled_date->toDateString())->toBe($futureDate)
        ->and($item->topic)->toBe(ContentCalendarTopic::Promotional)
        ->and($item->status)->toBe(ContentCalendarStatus::Ready)
        ->and($item->platformValues())->toContain(ContentCalendarPlatform::Instagram->value)
        ->and($item->platformValues())->toContain(ContentCalendarPlatform::Facebook->value)
        ->and($item->getMedia('attachments'))->toHaveCount(1)
        ->and($item->statusHistories)->not->toBeEmpty();
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
            'topic' => ContentCalendarTopic::Educational->value,
            'platforms' => [$item->platforms()->value('platform') ?? ContentCalendarPlatform::Instagram->value],
            'description' => 'Updated caption',
            'status' => ContentCalendarStatus::Ready->value,
        ])
        ->assertRedirect();

    expect($item->fresh()->description)->toBe('Updated caption')
        ->and($item->fresh()->status)->toBe(ContentCalendarStatus::Ready)
        ->and($item->fresh()->topic)->toBe(ContentCalendarTopic::Educational);
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
    $monthStart = now()->startOfMonth()->toDateString();

    $poster = ContentCalendarItem::factory()->for($company, 'company')->create([
        'scheduled_date' => $monthStart,
        'content_type' => ContentCalendarType::Poster,
        'status' => ContentCalendarStatus::Ready,
        'description' => 'Alpha post',
    ]);
    $poster->syncPlatforms([ContentCalendarPlatform::Instagram->value]);

    $reel = ContentCalendarItem::factory()->for($company, 'company')->create([
        'scheduled_date' => $monthStart,
        'content_type' => ContentCalendarType::Reel,
        'status' => ContentCalendarStatus::Draft,
        'description' => 'Beta post',
    ]);
    $reel->syncPlatforms([ContentCalendarPlatform::YouTube->value]);

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewContentCalendar);

    $this->actingAs($viewer->user)
        ->get("/tasks/content-calendar?client={$company->id}&month=".now()->format('Y-m').'&content_type=poster&search=Alpha')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('items', 1)->where('items.0.description', 'Alpha post'));
});

test('month navigation uses full calendar month', function () {
    $company = Company::factory()->create();

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewContentCalendar);

    $this->actingAs($viewer->user)
        ->get("/tasks/content-calendar?client={$company->id}&month=2026-09")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('period.start', '2026-09-01')
            ->where('period.end', '2026-09-30')
            ->where('period.month', '2026-09')
            ->where('period.previous_month', '2026-08')
            ->where('period.next_month', '2026-10'));
});

test('individual content share links work publicly', function () {
    Storage::fake('public');

    $item = ContentCalendarItem::factory()->create([
        'description' => 'Public caption only',
        'status' => ContentCalendarStatus::UnderReview,
    ]);
    $item->addMedia(UploadedFile::fake()->image('post.jpg'))->toMediaCollection('attachments');

    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);

    $this->get($link->publicUrl())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/content-share/show-item')
            ->where('item.description', 'Public caption only')
            ->where('can_respond', true)
            ->missing('item.id'));
});

test('client can approve content via share link', function () {
    $item = ContentCalendarItem::factory()->create([
        'status' => ContentCalendarStatus::UnderReview,
    ]);
    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);

    $this->post(route('content-share.short.approve', ['shortCode' => $link->short_code]))
        ->assertRedirect();

    expect($item->fresh()->status)->toBe(ContentCalendarStatus::Approved)
        ->and($item->fresh()->statusHistories()->where('to_status', 'approved')->exists())->toBeTrue();
});

test('client can request changes via share link with feedback', function () {
    $item = ContentCalendarItem::factory()->create([
        'status' => ContentCalendarStatus::UnderReview,
    ]);
    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);

    $this->post(route('content-share.short.request-changes', ['shortCode' => $link->short_code]), [
        'feedback' => 'Please adjust the headline',
    ])->assertRedirect();

    expect($item->fresh()->status)->toBe(ContentCalendarStatus::ChangesRequested)
        ->and($item->fresh()->client_feedback)->toBe('Please adjust the headline');
});

test('request changes requires feedback', function () {
    $item = ContentCalendarItem::factory()->create([
        'status' => ContentCalendarStatus::UnderReview,
    ]);
    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);

    $this->post(route('content-share.short.request-changes', ['shortCode' => $link->short_code]), [
        'feedback' => '',
    ])->assertSessionHasErrors('feedback');
});

test('branded content share urls use client slug', function () {
    $company = Company::factory()->create(['name' => 'Law Associates']);
    $item = ContentCalendarItem::factory()->for($company, 'company')->create([
        'status' => ContentCalendarStatus::UnderReview,
    ]);
    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);

    expect($link->publicUrl())->toContain('/law-associates/'.$link->short_code);

    $this->get($link->publicUrl())
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('TaskManagement/content-share/show-item'));
});

test('month schedule share links work publicly', function () {
    $company = Company::factory()->create(['name' => 'Pro Logging']);
    $start = Carbon::parse('2026-09-01');
    $end = $start->copy()->endOfMonth();

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

    $this->get($link->publicUrl())->assertStatus(403);
});

test('viewing content calendar does not modify historical items', function () {
    $company = Company::factory()->create();
    $item = ContentCalendarItem::factory()->for($company, 'company')->create([
        'description' => 'Stable caption',
        'scheduled_date' => now()->subMonths(2)->toDateString(),
    ]);

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewContentCalendar);

    $this->actingAs($viewer->user)
        ->get("/tasks/content-calendar?client={$company->id}&month=".$item->scheduled_date->format('Y-m'))
        ->assertOk();

    expect($item->fresh()->description)->toBe('Stable caption');
});

test('holiday post can be created manually without auto-creating other posts', function () {
    $company = Company::factory()->create([
        'holiday_india_enabled' => true,
        'holiday_usa_enabled' => false,
    ]);

    $holiday = Holiday::query()->create([
        'country' => 'india',
        'region' => null,
        'name' => 'Test Festival',
        'date' => '2026-09-14',
        'year' => 2026,
        'holiday_type' => 'festival',
        'description' => null,
    ]);

    $manager = employeeWith(Ability::AccessTasks, Ability::ManageContentCalendar);

    $this->actingAs($manager->user)
        ->post('/tasks/content-calendar/holiday-post', [
            'tm_company_id' => $company->id,
            'holiday_id' => $holiday->id,
        ])
        ->assertRedirect();

    $item = ContentCalendarItem::query()->where('tm_company_id', $company->id)->sole();

    expect($item->topic)->toBe(ContentCalendarTopic::FestivalHoliday)
        ->and($item->description)->toBe('Test Festival')
        ->and($item->status)->toBe(ContentCalendarStatus::Draft)
        ->and(ContentCalendarItem::query()->count())->toBe(1);
});

test('excel import preview and confirm skips duplicates and does not touch creatives', function () {
    Storage::fake('local');

    $company = Company::factory()->create();
    ContentCalendarItem::factory()->for($company, 'company')->create([
        'scheduled_date' => '2026-09-01',
        'post_number' => 1,
        'description' => 'Existing',
    ]);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['Post #', 'Date', 'Format', 'Topic', 'Platforms', 'Description', 'Caption', 'Hashtags', 'Notes'],
        [1, '2026-09-01', 'Poster', 'Educational', 'Facebook, Instagram, LinkedIn', 'Dup', '', '', ''],
        [2, '2026-09-02', 'Reel', 'Promotional', 'Facebook, Instagram, YouTube', 'New post', 'Cap', '#a', 'Note'],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'cc');
    (new Xlsx($spreadsheet))->save($path);
    $file = new UploadedFile($path, 'plan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $manager = employeeWith(Ability::AccessTasks, Ability::ManageContentCalendar);

    $this->actingAs($manager->user)
        ->post('/tasks/content-calendar/import/preview', [
            'client' => $company->id,
            'file' => $file,
        ])
        ->assertRedirect();

    $this->actingAs($manager->user)
        ->post('/tasks/content-calendar/import/confirm')
        ->assertRedirect();

    expect(ContentCalendarItem::query()->where('tm_company_id', $company->id)->count())->toBe(2)
        ->and(ContentCalendarItem::query()->where('description', 'Existing')->exists())->toBeTrue()
        ->and(ContentCalendarItem::query()->where('description', 'New post')->where('status', 'draft')->exists())->toBeTrue();

    $imported = ContentCalendarItem::query()->where('description', 'New post')->first();
    expect($imported?->platformValues())->toContain('facebook')
        ->and($imported?->platformValues())->toContain('instagram')
        ->and($imported?->platformValues())->toContain('youtube');
});

test('send for review moves ready item to client review', function () {
    $item = ContentCalendarItem::factory()->create([
        'status' => ContentCalendarStatus::Ready,
    ]);
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageContentCalendar, Ability::ShareContentCalendar);

    $this->actingAs($manager->user)
        ->post("/tasks/content-calendar/{$item->id}/send-for-review")
        ->assertRedirect();

    expect($item->fresh()->status)->toBe(ContentCalendarStatus::UnderReview);
});
