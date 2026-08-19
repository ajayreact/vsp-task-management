<?php

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Core\Enums\Ability;

test('employee is marked present when checking in before the office late time', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
        'late_check_in_time' => '09:30:00',
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->travelTo(today()->setTime(9, 0));

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::Present);

    $this->actingAs($employee->user)
        ->get('/attendance/mark')
        ->assertInertia(fn ($page) => $page
            ->where('today.status', 'present')
            ->where('today.status_label', 'Working')
            ->where('today.can_check_out', true));
});

test('employee is marked late when checking in after the office late time', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
        'late_check_in_time' => '09:30:00',
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->travelTo(today()->setTime(10, 15));

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::Late);

    $this->actingAs($employee->user)
        ->get('/attendance/mark')
        ->assertInertia(fn ($page) => $page
            ->where('today.status', 'late')
            ->where('today.status_label', 'Late')
            ->where('today.can_check_out', true)
            ->where('today.can_start_break', true));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance?status=late')
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.filter.status', 'late')
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.status', 'late')
            ->where('snapshot.records.0.status_label', 'Late'));
});

test('resume work restores late status after a break', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
        'late_check_in_time' => '09:30:00',
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->travelTo(today()->setTime(10, 0));

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ]);

    $this->actingAs($employee->user)->post('/attendance/break/start')->assertSessionHas('success');
    $this->actingAs($employee->user)->post('/attendance/break/resume')->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::Late);
});
