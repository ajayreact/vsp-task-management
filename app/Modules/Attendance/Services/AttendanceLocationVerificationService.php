<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Data\LocationVerificationResult;
use App\Modules\Attendance\Enums\AttendanceAction;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Attendance\Support\GpsGeofence;
use App\Modules\Attendance\Support\OfficeNetworkVerifier;
use App\Modules\Core\Models\Employee;
use App\Services\EmployeeOfficeAssignmentService;

class AttendanceLocationVerificationService
{
    public const OUTSIDE_PREMISES_MESSAGE = 'You must be within the office premises to mark attendance.';

    public function __construct(
        protected EmployeeOfficeAssignmentService $officeAssignments,
        protected OfficeNetworkVerifier $network,
    ) {}

    public function verify(
        Employee $employee,
        AttendanceAction $action,
        float $latitude,
        float $longitude,
        ?string $clientIp = null,
    ): LocationVerificationResult {
        $office = $this->officeAssignments->assignedOfficeFor($employee);

        if ($this->shouldBypassLocationRestrictions($employee)) {
            return $this->superAdminBypassResult(
                $action,
                $latitude,
                $longitude,
                $office,
            );
        }

        if ($office === null) {
            return $this->failure(
                $action,
                'You do not have an office location assigned.',
            );
        }

        if (! $office->is_active) {
            return $this->failure(
                $action,
                'Your assigned office is not active.',
                $office,
                0,
            );
        }

        $distance = GpsGeofence::distanceInMeters(
            $latitude,
            $longitude,
            (float) $office->latitude,
            (float) $office->longitude,
        );

        if ($distance > $office->allowed_gps_radius_meters) {
            return $this->failure(
                $action,
                self::OUTSIDE_PREMISES_MESSAGE,
                $office,
                $distance,
            );
        }

        if (! $this->network->isAuthorized($clientIp, $office)) {
            return $this->failure(
                $action,
                OfficeNetworkVerifier::UNAUTHORIZED_NETWORK_MESSAGE,
                $office,
                $distance,
                networkVerified: false,
            );
        }

        return new LocationVerificationResult(
            passed: true,
            message: 'Location verified.',
            action: $action,
            distanceMeters: $distance,
            allowedRadiusMeters: $office->allowed_gps_radius_meters,
            officeId: $office->id,
            officeName: $office->name,
            networkVerificationRequired: $office->network_verification_enabled,
            networkVerified: true,
        );
    }

    protected function shouldBypassLocationRestrictions(Employee $employee): bool
    {
        return $employee->user?->isSuperAdmin() ?? false;
    }

    protected function superAdminBypassResult(
        AttendanceAction $action,
        float $latitude,
        float $longitude,
        ?OfficeLocation $assignedOffice,
    ): LocationVerificationResult {
        $office = $assignedOffice ?? $this->resolveFallbackOffice();

        $distance = $office !== null
            ? GpsGeofence::distanceInMeters(
                $latitude,
                $longitude,
                (float) $office->latitude,
                (float) $office->longitude,
            )
            : 0;

        return new LocationVerificationResult(
            passed: true,
            message: 'Location verified.',
            action: $action,
            distanceMeters: $distance,
            allowedRadiusMeters: $office?->allowed_gps_radius_meters ?? 0,
            officeId: $office?->id,
            officeName: $office?->name,
            networkVerificationRequired: $office?->network_verification_enabled ?? false,
            networkVerified: null,
        );
    }

    protected function resolveFallbackOffice(): ?OfficeLocation
    {
        return OfficeLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->first()
            ?? OfficeLocation::query()->orderBy('name')->first();
    }

    protected function failure(
        AttendanceAction $action,
        string $message,
        ?OfficeLocation $office = null,
        float $distanceMeters = 0,
        ?bool $networkVerified = null,
    ): LocationVerificationResult {
        return new LocationVerificationResult(
            passed: false,
            message: $message,
            action: $action,
            distanceMeters: $distanceMeters,
            allowedRadiusMeters: $office?->allowed_gps_radius_meters ?? 0,
            officeId: $office?->id,
            officeName: $office?->name,
            networkVerificationRequired: $office?->network_verification_enabled ?? false,
            networkVerified: $networkVerified,
        );
    }
}
