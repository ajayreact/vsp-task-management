<?php

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Core\Models\Employee;

test('super admin can open the attendance dashboard', function () {
    Employee::factory()->count(3)->create();

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/dashboard')
            ->has('snapshot.overview', 6)
            ->has('snapshot.records')
            ->where('snapshot.overview.0.key', 'total_employees')
            ->where('snapshot.overview.0.count', 3)
            ->where('snapshot.overview.1.key', 'present_today')
            ->where('snapshot.overview.1.count', 0)
            ->where('snapshot.overview.2.key', 'absent_today')
            ->where('snapshot.overview.2.count', 3));
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
            ->where('snapshot.overview.1.count', 1)
            ->where('snapshot.overview.2.count', 1)
            ->where('snapshot.overview.3.count', 1)
            ->where('snapshot.overview.4.count', 1)
            ->where('snapshot.overview.5.count', 1));
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
            ->has('snapshot.records', 2));
});
