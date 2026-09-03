<?php

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Enums\WfhRequestType;
use App\Modules\Attendance\Enums\WorkMode;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Attendance\Notifications\AttendanceDatabaseNotification;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\WorkArrangement;
use App\Modules\Core\Models\Employee;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
});

test('office employee can check in with gps and work mode is office', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::Present)
        ->and($entry->work_mode)->toBe(WorkMode::Office);
});

test('approved wfh employee can check in without gps', function () {
    $employee = employeeWith(Ability::AccessTasks);

    WfhRequest::factory()->approved()->create([
        'employee_id' => $employee->id,
        'start_date' => today(),
        'end_date' => today(),
    ]);

    $this->actingAs($employee->user)
        ->post('/attendance/check-in/wfh')
        ->assertRedirect()
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::Present)
        ->and($entry->work_mode)->toBe(WorkMode::Wfh)
        ->and($entry->check_in_latitude)->toBeNull()
        ->and($entry->check_in_longitude)->toBeNull();
});

test('unapproved employee cannot check in as wfh', function () {
    $employee = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->post('/attendance/check-in/wfh')
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(AttendanceEntry::query()->where('employee_id', $employee->id)->count())->toBe(0);
});

test('wfh employee can check out and working hours are stored', function () {
    $employee = employeeWith(Ability::AccessTasks);

    WfhRequest::factory()->approved()->create([
        'employee_id' => $employee->id,
        'start_date' => today(),
        'end_date' => today(),
    ]);

    $this->actingAs($employee->user)->post('/attendance/check-in/wfh')->assertRedirect();

    $this->travel(2)->hours();

    $this->actingAs($employee->user)
        ->post('/attendance/check-out/wfh')
        ->assertRedirect()
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->work_mode)->toBe(WorkMode::Wfh)
        ->and($entry->status)->toBe(AttendanceStatus::CheckedOut)
        ->and($entry->check_out_at)->not->toBeNull()
        ->and($entry->net_working_seconds)->toBeGreaterThan(0);
});

test('employee can submit wfh request and duplicate requests are blocked', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $start = today()->addDay()->toDateString();

    $this->actingAs($employee->user)
        ->post('/attendance/wfh', [
            'start_date' => $start,
            'end_date' => $start,
            'reason' => 'Doctor appointment follow-up.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(WfhRequest::query()->where('employee_id', $employee->id)->count())->toBe(1);

    $this->actingAs($employee->user)
        ->post('/attendance/wfh', [
            'start_date' => $start,
            'end_date' => $start,
            'reason' => 'Duplicate attempt.',
        ])
        ->assertSessionHasErrors('start_date');
});

test('employee can submit wfh request for a date range', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $start = today()->addDays(2)->toDateString();
    $end = today()->addDays(4)->toDateString();

    $this->actingAs($employee->user)
        ->post('/attendance/wfh', [
            'start_date' => $start,
            'end_date' => $end,
            'reason' => 'Family travel.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $request = WfhRequest::query()->where('employee_id', $employee->id)->sole();

    expect($request->type)->toBe(WfhRequestType::Request)
        ->and($request->start_date->toDateString())->toBe($start)
        ->and($request->end_date->toDateString())->toBe($end)
        ->and($request->status)->toBe(WfhRequestStatus::Pending);
});

test('super admin can approve and reject wfh requests', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $pending = WfhRequest::factory()->create([
        'employee_id' => $employee->id,
        'start_date' => today()->addDays(2),
        'end_date' => today()->addDays(2),
    ]);
    $toReject = WfhRequest::factory()->create([
        'employee_id' => $employee->id,
        'start_date' => today()->addDays(3),
        'end_date' => today()->addDays(3),
    ]);

    $admin = superAdmin();

    $this->actingAs($admin)
        ->post("/admin/attendance/wfh/{$pending->id}/approve")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($pending->fresh()->status)->toBe(WfhRequestStatus::Approved)
        ->and($pending->fresh()->approved_by_user_id)->toBe($admin->id);

    Notification::assertSentTo($employee->user, AttendanceDatabaseNotification::class);

    $this->actingAs($admin)
        ->post("/admin/attendance/wfh/{$toReject->id}/reject")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($toReject->fresh()->status)->toBe(WfhRequestStatus::Rejected);
});

test('operations user with manage wfh permission can assign and approve', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $operations = staffWith(Ability::ManageWfhRequests);
    $start = today()->addDay()->toDateString();

    $this->actingAs($operations)
        ->post('/admin/attendance/wfh/assign', [
            'employee_id' => $employee->id,
            'start_date' => $start,
            'end_date' => $start,
            'reason' => 'Management assigned WFH.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $assignment = WfhRequest::query()->where('employee_id', $employee->id)->sole();

    expect($assignment->type)->toBe(WfhRequestType::Assignment)
        ->and($assignment->status)->toBe(WfhRequestStatus::Assigned)
        ->and($assignment->assigned_by_user_id)->toBe($operations->id);

    Notification::assertSentTo(
        $employee->user,
        AttendanceDatabaseNotification::class,
        fn (AttendanceDatabaseNotification $notification) => $notification->payload['event'] === 'attendance.wfh.assigned',
    );

    $pending = WfhRequest::factory()->create([
        'employee_id' => $employee->id,
        'start_date' => today()->addDays(5),
        'end_date' => today()->addDays(5),
    ]);

    $this->actingAs($operations)
        ->post("/admin/attendance/wfh/{$pending->id}/approve")
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('operations can assign wfh for multiple dates and employee can check in on each day', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $admin = superAdmin();
    $start = today()->addDay();
    $end = today()->addDays(3);

    $this->actingAs($admin)
        ->post('/admin/attendance/wfh/assign', [
            'employee_id' => $employee->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'Project sprint from home.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
        $this->travelTo($day->copy()->setTime(9, 15));

        $this->actingAs($employee->user)
            ->post('/attendance/check-in/wfh')
            ->assertRedirect()
            ->assertSessionHas('success');

        $entry = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $day)
            ->sole();

        expect($entry->work_mode)->toBe(WorkMode::Wfh);
    }
});

test('employee cannot check in as wfh outside assigned date range', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $admin = superAdmin();
    $tomorrow = today()->addDay();

    WfhRequest::factory()->assigned()->create([
        'employee_id' => $employee->id,
        'start_date' => $tomorrow,
        'end_date' => $tomorrow,
        'assigned_by_user_id' => $admin->id,
        'approved_by_user_id' => $admin->id,
    ]);

    $this->actingAs($employee->user)
        ->post('/attendance/check-in/wfh')
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(AttendanceEntry::query()->where('employee_id', $employee->id)->count())->toBe(0);
});

test('direct assignment takes priority over approved employee request', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $admin = superAdmin();

    $request = WfhRequest::factory()->approved()->create([
        'employee_id' => $employee->id,
        'start_date' => today(),
        'end_date' => today(),
    ]);

    $assignment = WfhRequest::factory()->assigned()->create([
        'employee_id' => $employee->id,
        'start_date' => today(),
        'end_date' => today(),
        'assigned_by_user_id' => $admin->id,
        'approved_by_user_id' => $admin->id,
    ]);

    $authorized = app(\App\Modules\Attendance\Services\WfhRequestService::class)->authorizedFor($employee);

    expect($authorized?->id)->toBe($assignment->id)
        ->and($request->status)->toBe(WfhRequestStatus::Approved);
});

test('admin cannot create conflicting wfh assignments', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $admin = superAdmin();
    $date = today()->addDays(2)->toDateString();

    WfhRequest::factory()->assigned()->create([
        'employee_id' => $employee->id,
        'start_date' => $date,
        'end_date' => $date,
        'assigned_by_user_id' => $admin->id,
        'approved_by_user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post('/admin/attendance/wfh/assign', [
            'employee_id' => $employee->id,
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Conflicting assignment.',
        ])
        ->assertSessionHasErrors('start_date');
});

test('direct assignment rejects overlapping pending employee request', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $admin = superAdmin();
    $date = today()->addDays(2);

    $pending = WfhRequest::factory()->create([
        'employee_id' => $employee->id,
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $this->actingAs($admin)
        ->post('/admin/attendance/wfh/assign', [
            'employee_id' => $employee->id,
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'reason' => 'Operations override.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($pending->fresh()->status)->toBe(WfhRequestStatus::Rejected);
});

test('admin can edit and cancel direct assignments without deleting history', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $admin = superAdmin();
    $assignment = WfhRequest::factory()->assigned()->create([
        'employee_id' => $employee->id,
        'start_date' => today()->addDays(4),
        'end_date' => today()->addDays(6),
        'assigned_by_user_id' => $admin->id,
        'approved_by_user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->put("/admin/attendance/wfh/{$assignment->id}", [
            'employee_id' => $employee->id,
            'start_date' => today()->addDays(4)->toDateString(),
            'end_date' => today()->addDays(5)->toDateString(),
            'reason' => 'Shortened assignment.',
            'notes' => 'Updated by admin.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($assignment->fresh()->end_date->toDateString())->toBe(today()->addDays(5)->toDateString())
        ->and($assignment->fresh()->notes)->toBe('Updated by admin.');

    $this->actingAs($admin)
        ->post("/admin/attendance/wfh/{$assignment->id}/cancel")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($assignment->fresh()->status)->toBe(WfhRequestStatus::Cancelled)
        ->and(WfhRequest::query()->whereKey($assignment->id)->exists())->toBeTrue();
});

test('remote employee can check in without gps and work mode is wfh', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $employee->update(['work_arrangement' => WorkArrangement::Remote]);

    $this->actingAs($employee->user)
        ->post('/attendance/check-in/wfh')
        ->assertRedirect()
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->work_mode)->toBe(WorkMode::Wfh)
        ->and($entry->check_in_latitude)->toBeNull();
});

test('hybrid employee still requires wfh authorization', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $employee->update(['work_arrangement' => WorkArrangement::Hybrid]);

    $this->actingAs($employee->user)
        ->post('/attendance/check-in/wfh')
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('employee cannot assign wfh to themselves', function () {
    $employee = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->post('/admin/attendance/wfh/assign', [
            'employee_id' => $employee->id,
            'start_date' => today()->addDay()->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
            'reason' => 'Self assignment attempt.',
        ])
        ->assertForbidden();
});

test('staff without manage wfh permission cannot access wfh management', function () {
    $this->actingAs(staffWith())
        ->get('/admin/attendance/wfh')
        ->assertForbidden();
});

test('attendance dashboard splits office and wfh present counts', function () {
    $officeEmployee = Employee::factory()->create();
    $wfhEmployee = Employee::factory()->create();

    AttendanceEntry::query()->create([
        'employee_id' => $officeEmployee->id,
        'attendance_date' => today(),
        'status' => AttendanceStatus::Present,
        'work_mode' => WorkMode::Office,
        'check_in_at' => now(),
    ]);

    AttendanceEntry::query()->create([
        'employee_id' => $wfhEmployee->id,
        'attendance_date' => today(),
        'status' => AttendanceStatus::Present,
        'work_mode' => WorkMode::Wfh,
        'check_in_at' => now(),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.overview.1.key', 'present_today')
            ->where('snapshot.overview.1.count', 2)
            ->where('snapshot.overview.2.key', 'office_present')
            ->where('snapshot.overview.2.count', 1)
            ->where('snapshot.overview.3.key', 'wfh_present')
            ->where('snapshot.overview.3.count', 1));
});

test('wfh management page exposes type and assignment metadata', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $admin = superAdmin();

    WfhRequest::factory()->assigned()->create([
        'employee_id' => $employee->id,
        'start_date' => today()->addDay(),
        'end_date' => today()->addDays(2),
        'assigned_by_user_id' => $admin->id,
        'approved_by_user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get('/admin/attendance/wfh')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/wfh/manage')
            ->has('requests', 1)
            ->where('requests.0.type', 'assignment')
            ->where('requests.0.can_cancel', true));
});
