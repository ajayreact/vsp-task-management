<?php

use App\Modules\Attendance\Enums\AttendanceAction;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Attendance\Services\AttendanceLocationVerificationService;
use App\Modules\Attendance\Support\OfficeNetworkVerifier;
use App\Modules\Core\Enums\Ability;

test('super admin bypasses gps geofence restrictions during location verification', function () {
    $employee = superAdminEmployee();
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

    $result = app(AttendanceLocationVerificationService::class)->verify(
        $employee,
        AttendanceAction::CheckIn,
        28.704060,
        77.102493,
    );

    expect($result->passed)->toBeTrue()
        ->and($result->officeId)->toBe($office->id);
});

test('super admin bypasses office network ip restrictions during check in', function () {
    $employee = superAdminEmployee();
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'network_verification_enabled' => true,
        'authorized_public_ips' => ['203.0.113.10'],
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
        ->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $entry = AttendanceEntry::query()->where('employee_id', $employee->id)->sole();

    expect($entry->status)->toBe(AttendanceStatus::Present)
        ->and($entry->office_location_id)->toBe($office->id);
});

test('super admin bypasses inactive assigned office restrictions', function () {
    $employee = superAdminEmployee();
    $office = OfficeLocation::factory()->inactive()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $result = app(AttendanceLocationVerificationService::class)->verify(
        $employee,
        AttendanceAction::CheckIn,
        28.704060,
        77.102493,
    );

    expect($result->passed)->toBeTrue()
        ->and($result->officeId)->toBe($office->id);
});

test('super admin without an assigned office can still verify and check in using a fallback office', function () {
    $employee = superAdminEmployee();
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'is_active' => true,
    ]);

    $this->actingAs($employee->user)
        ->postJson('/attendance/verify-location', [
            'action' => 'check_in',
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertOk()
        ->assertJson([
            'passed' => true,
            'office' => [
                'id' => $office->id,
                'name' => $office->name,
            ],
        ]);

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(AttendanceEntry::query()->where('employee_id', $employee->id)->value('office_location_id'))
        ->toBe($office->id);
});

test('super admin can check out from outside the office after bypassing location checks', function () {
    $employee = superAdminEmployee();
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'network_verification_enabled' => true,
        'authorized_public_ips' => ['203.0.113.10'],
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
        ->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertSessionHas('success');

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
        ->actingAs($employee->user)
        ->post('/attendance/check-out', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(AttendanceEntry::query()->where('employee_id', $employee->id)->value('status'))
        ->toBe(AttendanceStatus::CheckedOut);
});

test('normal employees still fail gps and ip location verification', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'network_verification_enabled' => true,
        'authorized_public_ips' => ['203.0.113.10'],
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $gpsResult = app(AttendanceLocationVerificationService::class)->verify(
        $employee,
        AttendanceAction::CheckIn,
        28.704060,
        77.102493,
        '203.0.113.10',
    );

    expect($gpsResult->passed)->toBeFalse()
        ->and($gpsResult->message)->toBe(AttendanceLocationVerificationService::OUTSIDE_PREMISES_MESSAGE);

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
        ->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', OfficeNetworkVerifier::UNAUTHORIZED_NETWORK_MESSAGE);
});

test('super admin override does not bypass attendance workflow rules', function () {
    $employee = superAdminEmployee();
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
        ->assertSessionHas('success');

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'You have already checked in today.');
});

test('super admin with no assigned office sees an enabled check in action on the attendance page', function () {
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'is_active' => true,
    ]);

    $employee = superAdminEmployee();

    $this->actingAs($employee->user)
        ->get('/attendance/mark')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/mark')
            ->where('office', null)
            ->where('can_mark_attendance', true)
            ->where('location_bypass_enabled', true)
            ->where('location_fallback.latitude', (float) $office->latitude)
            ->where('location_fallback.longitude', (float) $office->longitude)
            ->where('today.can_check_in', true));
});

test('super admin with no assigned office can check in from the attendance page', function () {
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'is_active' => true,
    ]);

    $employee = superAdminEmployee();

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(AttendanceEntry::query()->where('employee_id', $employee->id)->value('office_location_id'))
        ->toBe($office->id);
});

test('super admin with no assigned office can check out from the attendance page', function () {
    OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'is_active' => true,
    ]);

    $employee = superAdminEmployee();

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertSessionHas('success');

    $this->actingAs($employee->user)
        ->post('/attendance/check-out', [
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(AttendanceEntry::query()->where('employee_id', $employee->id)->value('status'))
        ->toBe(AttendanceStatus::CheckedOut);
});

test('normal employee with no assigned office remains blocked on the attendance page', function () {
    $employee = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->get('/attendance/mark')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/mark')
            ->where('office', null)
            ->where('can_mark_attendance', false)
            ->where('location_bypass_enabled', false)
            ->where('location_fallback', null)
            ->where('today.can_check_in', true));

    $this->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.613939,
            'longitude' => 77.209023,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'You do not have an office location assigned.');

    expect(AttendanceEntry::query()->where('employee_id', $employee->id)->exists())->toBeFalse();
});
