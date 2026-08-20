<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceCheckInOutService;
use App\Services\EmployeeOfficeAssignmentService;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceMarkController extends Controller
{
    public function __construct(
        protected EmployeeOfficeAssignmentService $officeAssignments,
        protected AttendanceCheckInOutService $attendance,
    ) {}

    public function show(): Response
    {
        $this->authorize('markOwnAttendance');

        $employee = request()->user()?->employee;

        abort_if($employee === null, 403);

        $user = request()->user();
        $office = $this->officeAssignments->assignedOfficeFor($employee);
        $locationBypassEnabled = $user?->isSuperAdmin() ?? false;
        $canMarkAttendance = $locationBypassEnabled || ($office !== null && $office->is_active);

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
            'today' => $this->attendance->todaySnapshot($employee),
        ]);
    }
}
