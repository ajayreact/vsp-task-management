<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Services\EmployeeOfficeAssignmentService;

/**
 * Shared page data for /attendance/mark and the Dashboard attendance card.
 */
class AttendanceMarkPayload
{
    public function __construct(
        protected EmployeeOfficeAssignmentService $officeAssignments,
        protected AttendanceCheckInOutService $attendance,
        protected WfhRequestService $wfhRequests,
    ) {}

    /**
     * @return array{
     *     office: array<string, mixed>|null,
     *     can_mark_attendance: bool,
     *     location_bypass_enabled: bool,
     *     location_fallback: array{latitude: float, longitude: float}|null,
     *     today: array<string, mixed>
     * }|null
     */
    public function forUser(User $user): ?array
    {
        if (! $user->can('markOwnAttendance')) {
            return null;
        }

        $employee = $user->employee;

        if ($employee === null) {
            return null;
        }

        return $this->forEmployee($user, $employee);
    }

    /**
     * @return array{
     *     office: array<string, mixed>|null,
     *     can_mark_attendance: bool,
     *     location_bypass_enabled: bool,
     *     location_fallback: array{latitude: float, longitude: float}|null,
     *     today: array<string, mixed>
     * }
     */
    public function forEmployee(User $user, Employee $employee): array
    {
        $office = $this->officeAssignments->assignedOfficeFor($employee);
        $locationBypassEnabled = $user->isSuperAdmin() || $employee->work_arrangement->bypassesOfficeGps();
        $wfhAuthorizedToday = $this->wfhRequests->isAuthorizedFor($employee);
        $canMarkAttendance = $locationBypassEnabled || $wfhAuthorizedToday || ($office !== null && $office->is_active);
        $locationFallback = $this->locationFallbackCoordinates($office, $locationBypassEnabled);

        return [
            'office' => $office ? [
                'id' => $office->id,
                'name' => $office->name,
                'address' => $office->address,
                'allowed_gps_radius_meters' => $office->allowed_gps_radius_meters,
                'network_verification_enabled' => $office->network_verification_enabled,
                'is_active' => $office->is_active,
            ] : null,
            'can_mark_attendance' => $canMarkAttendance,
            'location_bypass_enabled' => $locationBypassEnabled,
            'location_fallback' => $locationFallback,
            'today' => $this->attendance->todaySnapshot($employee),
        ];
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    protected function locationFallbackCoordinates(?OfficeLocation $assignedOffice, bool $locationBypassEnabled): ?array
    {
        if (! $locationBypassEnabled) {
            return null;
        }

        $office = $assignedOffice ?? OfficeLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->first()
            ?? OfficeLocation::query()->orderBy('name')->first();

        if ($office === null) {
            return null;
        }

        return [
            'latitude' => (float) $office->latitude,
            'longitude' => (float) $office->longitude,
        ];
    }
}
