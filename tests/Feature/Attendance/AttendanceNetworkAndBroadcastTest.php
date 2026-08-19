<?php

use App\Modules\Attendance\Enums\AttendanceAction;
use App\Modules\Attendance\Events\AttendanceDashboardUpdated;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Attendance\Services\AttendanceLocationVerificationService;
use App\Modules\Attendance\Support\OfficeNetworkVerifier;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\Attendance\Services\AttendanceBroadcastService;
use Illuminate\Support\Facades\Event;

test('office network verifier matches exact ip and cidr ranges', function () {
    $verifier = app(OfficeNetworkVerifier::class);
    $office = OfficeLocation::factory()->create([
        'network_verification_enabled' => true,
        'authorized_public_ips' => ['203.0.113.10', '198.51.100.0/24'],
    ]);

    expect($verifier->isAuthorized('203.0.113.10', $office))->toBeTrue()
        ->and($verifier->isAuthorized('198.51.100.55', $office))->toBeTrue()
        ->and($verifier->isAuthorized('203.0.113.11', $office))->toBeFalse()
        ->and($verifier->isAuthorized('10.0.0.1', $office))->toBeFalse();
});

test('network verification is skipped when disabled on office', function () {
    $employee = Employee::factory()->create();
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 100,
        'network_verification_enabled' => false,
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
        '203.0.113.99',
    );

    expect($result->passed)->toBeTrue();
});

test('location verification blocks check in when office network ip does not match', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
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
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertRedirect()
        ->assertSessionHas('error', OfficeNetworkVerifier::UNAUTHORIZED_NETWORK_MESSAGE);
});

test('location verification passes when gps and office network both match', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
        'network_verification_enabled' => true,
        'authorized_public_ips' => ['203.0.113.10'],
        'is_active' => true,
    ]);

    EmployeeOfficeAssignment::query()->create([
        'employee_id' => $employee->id,
        'office_location_id' => $office->id,
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->actingAs($employee->user)
        ->post('/attendance/check-in', [
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('verify location api reports network verification result', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $office = OfficeLocation::factory()->create([
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
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
        ->postJson('/attendance/verify-location', [
            'action' => 'check_in',
            'latitude' => 28.614339,
            'longitude' => 77.209423,
        ])
        ->assertUnprocessable()
        ->assertJson([
            'passed' => false,
            'message' => OfficeNetworkVerifier::UNAUTHORIZED_NETWORK_MESSAGE,
            'network_verification_required' => true,
            'network_verified' => false,
        ]);
});

test('check in broadcasts attendance dashboard refresh to super admins', function () {
    configureReverbForChannelAuth();
    Event::fake([AttendanceDashboardUpdated::class]);

    $employee = employeeWith(Ability::AccessTasks);
    $superAdmin = superAdmin();
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

    Event::assertDispatched(AttendanceDashboardUpdated::class, function (AttendanceDashboardUpdated $event) use ($superAdmin) {
        return in_array($superAdmin->id, $event->recipientUserIds, true);
    });
});

test('attendance broadcast refresh does not throw when reverb is unavailable', function () {
    configureReverbForChannelAuth();
    superAdmin();

    $service = app(AttendanceBroadcastService::class);

    expect(fn () => $service->refresh())->not->toThrow(Throwable::class);
});

test('check in still succeeds when attendance dashboard broadcast is unavailable', function () {
    configureReverbForChannelAuth();
    superAdmin();

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
});

test('break actions broadcast attendance dashboard refresh', function () {
    configureReverbForChannelAuth();
    Event::fake([AttendanceDashboardUpdated::class]);

    superAdmin();
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
        ]);

    Event::fake([AttendanceDashboardUpdated::class]);

    $this->actingAs($employee->user)
        ->post('/attendance/break/start')
        ->assertSessionHas('success');

    Event::assertDispatched(AttendanceDashboardUpdated::class);

    $this->actingAs($employee->user)
        ->post('/attendance/break/resume')
        ->assertSessionHas('success');

    Event::assertDispatched(AttendanceDashboardUpdated::class, 2);
});
