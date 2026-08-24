<?php

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->withoutVite();
});

test('super admin can view the daily attendance table with all employees', function () {
    $present = Employee::factory()->create(['employee_code' => 'EMP-IN']);
    $absent = Employee::factory()->create(['employee_code' => 'EMP-OUT']);
    $date = today()->previous('Monday')->subWeek();

    AttendanceEntry::query()->create([
        'employee_id' => $present->id,
        'attendance_date' => $date,
        'status' => AttendanceStatus::Present,
        'check_in_at' => $date->copy()->setTime(9, 0),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$date->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/dashboard')
            ->has('monthlyReport.rows')
            ->has('dailyTable.records', 2)
            ->where('dailyTable.records.0.date', $date->toDateString())
            ->where('dailyTable.records.0.day', $date->format('l'))
            ->where('dailyTable.records.1.status', 'absent'));
});

test('daily attendance table can be filtered by employee and search', function () {
    $alpha = Employee::factory()->create(['employee_code' => 'EMP-AAA']);
    $beta = Employee::factory()->create(['employee_code' => 'EMP-BBB']);
    $date = today()->subDay();

    AttendanceEntry::query()->create([
        'employee_id' => $alpha->id,
        'attendance_date' => $date,
        'status' => AttendanceStatus::Present,
        'check_in_at' => $date->copy()->setTime(9, 0),
    ]);
    AttendanceEntry::query()->create([
        'employee_id' => $beta->id,
        'attendance_date' => $date,
        'status' => AttendanceStatus::Late,
        'check_in_at' => $date->copy()->setTime(10, 0),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$date->toDateString().'&employee_id='.$alpha->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('dailyTable.records', 1)
            ->where('dailyTable.records.0.employee_code', 'EMP-AAA'));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$date->toDateString().'&search=BBB')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('dailyTable.records', 1)
            ->where('dailyTable.records.0.employee_code', 'EMP-BBB'));
});

test('daily attendance table marks late check-ins correctly', function () {
    $employee = Employee::factory()->create(['employee_code' => 'EMP-LATE']);
    $office = OfficeLocation::factory()->create(['late_check_in_time' => '09:30:00']);
    $date = today()->subDays(3);

    AttendanceEntry::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
        'attendance_date' => $date,
        'status' => AttendanceStatus::Late,
        'check_in_at' => $date->copy()->setTime(10, 15),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$date->toDateString().'&status=late')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('dailyTable.records', 1)
            ->where('dailyTable.records.0.is_late', true)
            ->where('dailyTable.records.0.status', 'late'));
});

test('super admin can view monthly attendance report matrix', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

    $employee = Employee::factory()->create(['employee_code' => 'EMP-MONTH']);
    $monthStart = Carbon::parse('2026-08-01');

    AttendanceEntry::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => $monthStart->copy()->addDays(3),
        'status' => AttendanceStatus::Present,
        'check_in_at' => $monthStart->copy()->addDays(3)->setTime(9, 0),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?month=8&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('monthlyReport.rows', 1)
            ->where('monthlyReport.summary.total_employees', 1)
            ->where('monthlyReport.rows.0.totals.present', 1));

    Carbon::setTestNow();
});

test('monthly report treats saturday and sunday as off not absent', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

    $employee = Employee::factory()->create(['employee_code' => 'EMP-WEEKEND']);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?month=8&year=2026&employee_id='.$employee->id)
        ->assertOk()
        ->assertInertia(function ($page) {
            $days = collect($page->toArray()['props']['monthlyReport']['rows'][0]['days']);

            expect($days->firstWhere('date', '2026-08-01')['code'])->toBe('OFF')
                ->and($days->firstWhere('date', '2026-08-02')['code'])->toBe('OFF')
                ->and($days->firstWhere('date', '2026-08-04')['code'])->toBe('A');
        });

    Carbon::setTestNow();
});

test('monthly report can be filtered by department and office', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

    $department = Department::factory()->create(['name' => 'Engineering']);
    $office = OfficeLocation::factory()->create(['name' => 'HQ']);
    $included = Employee::factory()->create(['department_id' => $department->id]);
    $excluded = Employee::factory()->create();

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $included->id,
        'office_location_id' => $office->id,
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?month=8&year=2026&department_id='.$department->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('monthlyReport.rows', 1)
            ->where('monthlyReport.rows.0.employee_id', $included->id));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?month=8&year=2026&office_id='.$office->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('monthlyReport.rows', 1)
            ->where('monthlyReport.rows.0.employee_id', $included->id));

    Carbon::setTestNow();
});

test('super admin can inspect employee monthly detail from monthly report', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

    $employee = Employee::factory()->create(['employee_code' => 'EMP-DETAIL']);
    $date = Carbon::parse('2026-08-05');

    AttendanceEntry::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => $date,
        'status' => AttendanceStatus::CheckedOut,
        'check_in_at' => $date->copy()->setTime(9, 0),
        'check_out_at' => $date->copy()->setTime(18, 0),
        'net_working_seconds' => 28_800,
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?month=8&year=2026&detail_employee_id='.$employee->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('employeeDetail.employee.employee_code', 'EMP-DETAIL')
            ->where('employeeDetail.records.4.date', '2026-08-05')
            ->where('employeeDetail.records.4.report_code', 'P'));

    Carbon::setTestNow();
});

test('super admin can download monthly attendance excel export', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

    Employee::factory()->create(['employee_code' => 'EMP-XLS']);

    $response = $this->actingAs(superAdmin())
        ->get('/admin/attendance/export/monthly?month=8&year=2026');

    $response->assertOk();
    expect($response->headers->get('content-disposition'))
        ->toContain('VSP_Attendance_August_2026.xlsx');

    Carbon::setTestNow();
});

test('legacy monthly tab query still loads the combined attendance dashboard', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

    Employee::factory()->create();

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?tab=monthly&month=8&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/dashboard')
            ->has('monthlyReport.rows')
            ->has('dailyTable.records')
            ->has('snapshot.overview', 6));

    Carbon::setTestNow();
});

test('staff without super admin role cannot access monthly report or export', function () {
    $this->actingAs(staffWith())
        ->get('/admin/attendance?month=8&year=2026')
        ->assertForbidden();

    $this->actingAs(staffWith())
        ->get('/admin/attendance/export/monthly?month=8&year=2026')
        ->assertForbidden();
});

test('historical daily report does not change when viewing previous date', function () {
    $employee = Employee::factory()->create(['employee_code' => 'EMP-HIST']);
    $previousDate = today()->subDays(7);

    AttendanceEntry::query()->create([
        'employee_id' => $employee->id,
        'attendance_date' => $previousDate,
        'status' => AttendanceStatus::Present,
        'check_in_at' => $previousDate->copy()->setTime(9, 5),
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?date='.$previousDate->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.is_today', false)
            ->where('dailyTable.is_today', false)
            ->has('dailyTable.records', 1)
            ->where('dailyTable.records.0.employee_code', 'EMP-HIST'));
});

test('historical monthly report is not marked as today', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?month=7&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('monthlyReport.month', 7)
            ->where('monthlyReport.year', 2026));

    Carbon::setTestNow();
});
