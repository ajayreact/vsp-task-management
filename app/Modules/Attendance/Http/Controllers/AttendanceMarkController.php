<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Attendance\Services\AttendanceCheckInOutService;
use App\Modules\Attendance\Services\WfhRequestService;
use App\Services\EmployeeOfficeAssignmentService;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceMarkController extends Controller
{
    public function __construct(
        protected EmployeeOfficeAssignmentService $officeAssignments,
        protected AttendanceCheckInOutService $attendance,
        protected WfhRequestService $wfhRequests,
    ) {}

    public function show(): Response
    {
        $this->authorize('markOwnAttendance');

        $employee = request()->user()?->employee;

        abort_if($employee === null, 403);

        $user = request()->user();
        $office = $this->officeAssignments->assignedOfficeFor($employee);
        $locationBypassEnabled = ($user?->isSuperAdmin() ?? false) || $employee->work_arrangement->bypassesOfficeGps();
        $wfhAuthorizedToday = $this->wfhRequests->isAuthorizedFor($employee);
        $canMarkAttendance = $locationBypassEnabled || $wfhAuthorizedToday || ($office !== null && $office->is_active);
        $locationFallback = $this->locationFallbackCoordinates($office, $locationBypassEnabled);

        return Inertia::render('Attendance/mark', [
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
        ]);
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
