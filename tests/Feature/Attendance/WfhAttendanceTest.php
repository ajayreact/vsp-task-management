<?php

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Enums\WorkMode;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Core\Enums\Ability;
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
        'date' => today(),
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
        'date' => today(),
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
    $date = today()->addDay()->toDateString();

    $this->actingAs($employee->user)
        ->post('/attendance/wfh', [
            'date' => $date,
            'reason' => 'Doctor appointment follow-up.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(WfhRequest::query()->where('employee_id', $employee->id)->count())->toBe(1);

    $this->actingAs($employee->user)
        ->post('/attendance/wfh', [
            'date' => $date,
            'reason' => 'Duplicate attempt.',
        ])
        ->assertSessionHasErrors('date');
});

test('super admin can approve and reject wfh requests', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $pending = WfhRequest::factory()->create([
        'employee_id' => $employee->id,
        'date' => today()->addDays(2),
    ]);
    $toReject = WfhRequest::factory()->create([
        'employee_id' => $employee->id,
        'date' => today()->addDays(3),
    ]);

    $admin = superAdmin();

    $this->actingAs($admin)
        ->post("/admin/attendance/wfh/{$pending->id}/approve")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($pending->fresh()->status)->toBe(WfhRequestStatus::Approved)
        ->and($pending->fresh()->approved_by_user_id)->toBe($admin->id);

    $this->actingAs($admin)
        ->post("/admin/attendance/wfh/{$toReject->id}/reject")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($toReject->fresh()->status)->toBe(WfhRequestStatus::Rejected);
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

test('staff cannot access wfh management', function () {
    $this->actingAs(staffWith())
        ->get('/admin/attendance/wfh')
        ->assertForbidden();
});
