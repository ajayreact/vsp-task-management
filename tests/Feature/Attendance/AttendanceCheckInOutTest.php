<?php

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Attendance\Services\AttendanceLocationVerificationService;
use App\Modules\Core\Enums\Ability;

test('employee can check in with gps verification and cannot check in twice', function () {
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
        ->and($entry->work_mode)->toBe(\App\Modules\Attendance\Enums\WorkMode::Office)
        ->and($entry->office_location_id)->toBe($office->id)
        ->and($entry->check_in_at)->not->toBeNull()
        ->and((float) $entry->check_in_latitude)->toBe(28.614339)
        ->and((float) $entry->check_in_longitude)->toBe(77.209423)
        ->and($entry->check_out_at)->toBeNull();

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'You have already checked in today.');
});

test('employee cannot check out before checking in', function () {
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
        ->post('/attendance/check-out', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'You must check in before checking out.');
});

test('employee can check out after check in and cannot check out twice', function () {
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
        ->assertSessionHas('success');

    $this->travel(2)->hours();

    $this->actingAs($employee->user)
        ->post('/attendance/check-out', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::CheckedOut)
        ->and($entry->check_out_at)->not->toBeNull()
        ->and($entry->net_working_seconds)->toBeGreaterThanOrEqual(7200)
        ->and($entry->total_working_seconds)->toBe($entry->net_working_seconds)
        ->and((float) $entry->check_out_latitude)->toBe(28.614339);

    $this->actingAs($employee->user)
        ->post('/attendance/check-out', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'You have already checked out today.');
});

test('check in is blocked when gps verification fails', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', AttendanceLocationVerificationService::OUTSIDE_PREMISES_MESSAGE);

    expect(AttendanceEntry::query()->where('employee_id', $employee->id)->exists())->toBeFalse();
});

test('attendance page shows today status and super admin sees todays records', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create(['name' => 'HQ Office']);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->actingAs($employee->user)
        ->get('/attendance/mark')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/mark')
            ->where('today.status', 'not_checked_in')
            ->where('today.can_check_in', true));

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => (float) $office->latitude,
            'longitude' => (float) $office->longitude,
        ]);

    $this->actingAs($employee->user)
        ->get('/attendance/mark')
        ->assertInertia(fn ($page) => $page
            ->where('today.status', 'present')
            ->where('today.status_label', 'Working')
            ->where('today.can_check_out', true)
            ->where('today.can_start_break', true));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/dashboard')
            ->has('snapshot.records', 1)
            ->where('snapshot.records.0.employee_code', $employee->employee_code)
            ->where('snapshot.records.0.office', 'HQ Office'));
});
