<?php

use App\Modules\Attendance\Enums\AttendanceAction;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Attendance\Services\AttendanceLocationVerificationService;
use App\Modules\Attendance\Support\GpsGeofence;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;

test('gps geofence calculates distance between coordinates', function () {
    $officeLat = 28.613939;
    $officeLon = 77.209023;

    $nearLat = 28.614339;
    $nearLon = 77.209423;

    $distance = GpsGeofence::distanceInMeters($nearLat, $nearLon, $officeLat, $officeLon);

    expect($distance)->toBeGreaterThan(50)
        ->and($distance)->toBeLessThan(70);

    expect(GpsGeofence::isWithinRadius($nearLat, $nearLon, $officeLat, $officeLon, 100))->toBeTrue()
        ->and(GpsGeofence::isWithinRadius($nearLat, $nearLon, $officeLat, $officeLon, 10))->toBeFalse();
});

test('location verification passes when employee is inside office radius', function () {
    $employee = Employee::factory()->create();
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
        28.614339,
        77.209423,
    );

    expect($result->passed)->toBeTrue()
        ->and($result->message)->toBe('Location verified.')
        ->and($result->officeId)->toBe($office->id);
});

test('location verification blocks employee outside office radius', function () {
    $employee = Employee::factory()->create();
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
        AttendanceAction::CheckOut,
        28.704060,
        77.102493,
    );

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe(AttendanceLocationVerificationService::OUTSIDE_PREMISES_MESSAGE);
});

test('location verification requires an assigned active office', function () {
    $employee = Employee::factory()->create();

    $unassigned = app(AttendanceLocationVerificationService::class)->verify(
        $employee,
        AttendanceAction::CheckIn,
        28.613939,
        77.209023,
    );

    expect($unassigned->passed)->toBeFalse()
        ->and($unassigned->message)->toBe('You do not have an office location assigned.');

    $office = OfficeLocation::factory()->inactive()->create();

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $inactive = app(AttendanceLocationVerificationService::class)->verify(
        $employee,
        AttendanceAction::CheckIn,
        (float) $office->latitude,
        (float) $office->longitude,
    );

    expect($inactive->passed)->toBeFalse()
        ->and($inactive->message)->toBe('Your assigned office is not active.');
});

test('employee can verify location for check in and check out via api', function () {
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
        ->postJson('/attendance/verify-location', [
            'action' => 'check_in',
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertOk()
        ->assertJson([
            'passed' => true,
            'action' => 'check_in',
            'office' => [
                'id' => $office->id,
                'name' => $office->name,
            ],
        ]);

    $this->actingAs($employee->user)
        ->postJson('/attendance/verify-location', [
            'action' => 'check_out',
            'latitude' => 28.704060,
            'longitude' => 77.102493,
        ])
        ->assertUnprocessable()
        ->assertJson([
            'passed' => false,
            'message' => AttendanceLocationVerificationService::OUTSIDE_PREMISES_MESSAGE,
            'action' => 'check_out',
        ]);
});

test('users without an employee profile cannot verify attendance location', function () {
    $this->actingAs(staffWith(Ability::AccessTasks))
        ->postJson('/attendance/verify-location', [
            'action' => 'check_in',
            'latitude' => 28.613939,
            'longitude' => 77.209023,
        ])
        ->assertForbidden();
});

test('employee can open the mark attendance screen', function () {
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
            ->where('office.name', 'HQ Office')
            ->has('today'));
});
