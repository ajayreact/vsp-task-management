<?php

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceBreak;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Core\Enums\Ability;

function assignOfficeToEmployee($employee, ?OfficeLocation $office = null): OfficeLocation
{
    $office ??= OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    return $office;
}

function checkInEmployee($employee, OfficeLocation $office): AttendanceEntry
{
    test()->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => (float) $office->latitude + 0.0004,
            'longitude' => (float) $office->longitude + 0.0004,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    return AttendanceEntry::query()->where('employee_id', $employee->id)->sole();
}

test('employee can start break after check in and resume work', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = assignOfficeToEmployee($employee);

    checkInEmployee($employee, $office);

    $this->actingAs($employee->user)
        ->post('/attendance/break/start')
        ->assertRedirect()
        ->assertSessionHas('success', 'Break started.');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::OnBreak)
        ->and($entry->breaks)->toHaveCount(1)
        ->and($entry->breaks->first()->ended_at)->toBeNull();

    $this->actingAs($employee->user)
        ->get('/attendance/mark')
        ->assertInertia(fn ($page) => $page
            ->where('today.status', 'on_break')
            ->where('today.status_label', 'On break')
            ->where('today.can_start_break', false)
            ->where('today.can_resume_work', true)
            ->where('today.can_check_out', false));

    $this->travel(15)->minutes();

    $this->actingAs($employee->user)
        ->post('/attendance/break/resume')
        ->assertRedirect()
        ->assertSessionHas('success', 'Break ended. You are working again.');

    $entry->refresh();

    expect($entry->status)->toBe(AttendanceStatus::Present)
        ->and($entry->total_break_seconds)->toBeGreaterThanOrEqual(900)
        ->and($entry->breaks->first()->duration_seconds)->toBeGreaterThanOrEqual(900)
        ->and($entry->breaks->first()->ended_at)->not->toBeNull();
});

test('employee can take multiple breaks in one day', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = assignOfficeToEmployee($employee);

    checkInEmployee($employee, $office);

    $this->actingAs($employee->user)->post('/attendance/break/start')->assertSessionHas('success');
    $this->travel(10)->minutes();
    $this->actingAs($employee->user)->post('/attendance/break/resume')->assertSessionHas('success');

    $this->actingAs($employee->user)->post('/attendance/break/start')->assertSessionHas('success');
    $this->travel(5)->minutes();
    $this->actingAs($employee->user)->post('/attendance/break/resume')->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::Present)
        ->and(AttendanceBreak::query()->where('attendance_entry_id', $entry->id)->count())->toBe(2)
        ->and($entry->total_break_seconds)->toBeGreaterThanOrEqual(900);
});

test('employee cannot start break twice or resume without being on break', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = assignOfficeToEmployee($employee);

    checkInEmployee($employee, $office);

    $this->actingAs($employee->user)
        ->post('/attendance/break/start')
        ->assertSessionHas('success');

    $this->actingAs($employee->user)
        ->post('/attendance/break/start')
        ->assertRedirect()
        ->assertSessionHas('error', 'You are already on a break.');

    $this->actingAs($employee->user)
        ->post('/attendance/break/resume')
        ->assertSessionHas('success');

    $this->actingAs($employee->user)
        ->post('/attendance/break/resume')
        ->assertRedirect()
        ->assertSessionHas('error', 'You must be on a break to resume work.');
});

test('employee cannot check out while on break', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = assignOfficeToEmployee($employee);

    checkInEmployee($employee, $office);

    $this->actingAs($employee->user)->post('/attendance/break/start')->assertSessionHas('success');

    $this->actingAs($employee->user)
        ->post('/attendance/check-out', [
            'latitude' => (float) $office->latitude + 0.0004,
            'longitude' => (float) $office->longitude + 0.0004,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'End your break before checking out.');
});

test('checkout stores net working seconds after subtracting break time', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = assignOfficeToEmployee($employee);

    checkInEmployee($employee, $office);

    $this->travel(2)->hours();

    $this->actingAs($employee->user)->post('/attendance/break/start')->assertSessionHas('success');
    $this->travel(30)->minutes();
    $this->actingAs($employee->user)->post('/attendance/break/resume')->assertSessionHas('success');

    $this->travel(30)->minutes();

    $this->actingAs($employee->user)
        ->post('/attendance/check-out', [
            'latitude' => (float) $office->latitude + 0.0004,
            'longitude' => (float) $office->longitude + 0.0004,
        ])
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::CheckedOut)
        ->and($entry->total_break_seconds)->toBeGreaterThanOrEqual(1800)
        ->and($entry->net_working_seconds)->toBeGreaterThanOrEqual(5400)
        ->and($entry->total_working_seconds)->toBe($entry->net_working_seconds);
});

test('super admin dashboard shows working and on break status with break details', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = assignOfficeToEmployee($employee);

    checkInEmployee($employee, $office);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.records.0.status', 'present')
            ->where('snapshot.records.0.status_label', 'Working')
            ->where('snapshot.records.0.break_count', 0));

    $this->actingAs($employee->user)->post('/attendance/break/start')->assertSessionHas('success');
    $this->travel(5)->minutes();

    $this->actingAs(superAdmin())
        ->get('/admin/attendance')
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.records.0.status', 'on_break')
            ->where('snapshot.records.0.status_label', 'On break')
            ->where('snapshot.records.0.break_count', 1));
});
