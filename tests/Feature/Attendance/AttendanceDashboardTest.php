<?php

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Core\Models\Employee;

beforeEach(function () {
    $this->withoutVite();
});

test('super admin can open the attendance dashboard', function () {
    Employee::factory()->count(3)->create();

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/dashboard')
            ->has('snapshot.overview', 8)
            ->has('snapshot.records')
            ->where('snapshot.overview.0.key', 'total_employees')
            ->where('snapshot.overview.0.count', 3)
            ->where('snapshot.overview.1.key', 'present_today')
            ->where('snapshot.overview.1.count', 0)
            ->where('snapshot.overview.4.key', 'absent_today')
            ->where('snapshot.overview.4.count', 3));
});

test('staff without super admin role cannot open the attendance dashboard', function () {
    $this->actingAs(staffWith())
        ->get('/admin/attendance')
        ->assertForbidden();
});

test('attendance counts reflect today entries', function () {
    $present = Employee::factory()->create();
    $late = Employee::factory()->create();
    $onBreak = Employee::factory()->create();
    $checkedOut = Employee::factory()->create();
    Employee::factory()->create();

    $today = today();

    AttendanceEntry::query()->create([
        'employee_id' => $present->id,
        'attendance_date' => $today,
        'status' => AttendanceStatus::Present,
        'check_in_at' => now(),
    ]);
    AttendanceEntry::query()->create([
        'employee_id' => $late->id,
        'attendance_date' => $today,
        'status' => AttendanceStatus::Late,
        'check_in_at' => now(),
    ]);
    AttendanceEntry::query()->create([
        'employee_id' => $onBreak->id,
        'attendance_date' => $today,
        'status' => AttendanceStatus::OnBreak,
        'check_in_at' => now(),
    ]);
    AttendanceEntry::query()->create([
        'employee_id' => $checkedOut->id,
        'attendance_date' => $today,
        'status' => AttendanceStatus::CheckedOut,
        'check_in_at' => now(),
        'check_out_at' => now(),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.overview.0.count', 5)
            ->where('snapshot.overview.1.count', 4)
            ->where('snapshot.overview.2.count', 4)
            ->where('snapshot.overview.3.count', 0)
            ->where('snapshot.overview.4.count', 1)
            ->where('snapshot.overview.5.count', 1)
            ->where('snapshot.overview.6.count', 1)
            ->where('snapshot.overview.7.count', 1));
});

test('dashboard records can be filtered by status from kpi links', function () {
    $present = Employee::factory()->create(['employee_code' => 'EMP-PRES']);
    $late = Employee::factory()->create(['employee_code' => 'EMP-LATE']);
    $absent = Employee::factory()->create(['employee_code' => 'EMP-ABS']);

    $today = today();

    AttendanceEntry::query()->create([
        'employee_id' => $present->id,
        'attendance_date' => $today,
        'status' => AttendanceStatus::Present,
        'check_in_at' => now(),
    ]);
    AttendanceEntry::query()->create([
        'employee_id' => $late->id,
        'attendance_date' => $today,
        'status' => AttendanceStatus::Late,
        'check_in_at' => now(),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?status=present')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.filter.status', 'present')
            ->where('snapshot.filter.date', $today->toDateString())
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.employee_code', 'EMP-PRES'));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?status=late')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.filter.status', 'late')
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.employee_code', 'EMP-LATE'));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?status=absent')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.filter.status', 'absent')
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.employee_code', 'EMP-ABS')
            ->where('snapshot.records.0.status', 'absent')
            ->where('snapshot.records.0.status_label', 'Absent'));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.filter.status', null)
            ->where('snapshot.is_today', true)
            ->has('snapshot.records', 2));
});

test('the dashboard defaults to today when no date is provided', function () {
    $employee = Employee::factory()->create(['employee_code' => 'EMP-TODAY']);

    AttendanceEntry::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => today(),
        'status' => AttendanceStatus::Present,
        'check_in_at' => now(),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.date', today()->toDateString())
            ->where('snapshot.is_today', true)
            ->where('snapshot.filter.date', today()->toDateString())
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.employee_code', 'EMP-TODAY'));
});

test('the dashboard shows attendance records for a previous date', function () {
    $employee = Employee::factory()->create(['employee_code' => 'EMP-PAST']);
    $previousDate = today()->subDays(3);

    AttendanceEntry::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => $previousDate,
        'status' => AttendanceStatus::CheckedOut,
        'check_in_at' => $previousDate->copy()->setTime(9, 0),
        'check_out_at' => $previousDate->copy()->setTime(18, 0),
        'net_working_seconds' => 28_800,
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$previousDate->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.date', $previousDate->toDateString())
            ->where('snapshot.is_today', false)
            ->where('snapshot.overview.7.count', 1)
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.employee_code', 'EMP-PAST')
            ->where('snapshot.records.0.status', 'checked_out'));
});

test('the dashboard shows empty records and zero attendance kpis for a previous date with no entries', function () {
    Employee::factory()->count(2)->create();
    $previousDate = today()->subDays(5);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$previousDate->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.date', $previousDate->toDateString())
            ->where('snapshot.is_today', false)
            ->where('snapshot.overview.1.count', 0)
            ->where('snapshot.overview.2.count', 0)
            ->where('snapshot.overview.3.count', 0)
            ->where('snapshot.overview.4.count', 2)
            ->where('snapshot.overview.5.count', 0)
            ->where('snapshot.overview.6.count', 0)
            ->where('snapshot.overview.7.count', 0)
            ->has('snapshot.records', 0));
});

test('status filtering works for a previous date', function () {
    $present = Employee::factory()->create(['employee_code' => 'EMP-PAST-PRES']);
    $late = Employee::factory()->create(['employee_code' => 'EMP-PAST-LATE']);
    $previousDate = today()->subDays(2);

    AttendanceEntry::query()->create([
        'employee_id' => $present->id,
        'attendance_date' => $previousDate,
        'status' => AttendanceStatus::Present,
        'check_in_at' => $previousDate->copy()->setTime(9, 0),
    ]);
    AttendanceEntry::query()->create([
        'employee_id' => $late->id,
        'attendance_date' => $previousDate,
        'status' => AttendanceStatus::Late,
        'check_in_at' => $previousDate->copy()->setTime(10, 30),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$previousDate->toDateString().'&status=late')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.filter.status', 'late')
            ->where('snapshot.filter.date', $previousDate->toDateString())
            ->where('snapshot.overview.5.count', 1)
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.employee_code', 'EMP-PAST-LATE'));
});

test('absent employees are calculated for a previous date', function () {
    $present = Employee::factory()->create(['employee_code' => 'EMP-PAST-IN']);
    $absent = Employee::factory()->create(['employee_code' => 'EMP-PAST-OUT']);
    $previousDate = today()->subDays(4);

    AttendanceEntry::query()->create([
        'employee_id' => $present->id,
        'attendance_date' => $previousDate,
        'status' => AttendanceStatus::Present,
        'check_in_at' => $previousDate->copy()->setTime(9, 15),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$previousDate->toDateString().'&status=absent')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.filter.status', 'absent')
            ->where('snapshot.overview.4.count', 1)
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.employee_code', 'EMP-PAST-OUT')
            ->where('snapshot.records.0.status', 'absent'));
});
