<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Enums\WorkMode;
use App\Modules\Attendance\Exceptions\AttendanceWorkflowException;
use App\Modules\Attendance\Services\AttendanceCheckInOutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceWfhCheckOutController extends Controller
{
    public function __construct(protected AttendanceCheckInOutService $attendance) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $employee = $request->user()?->employee;

        abort_if($employee === null, 403);
        $this->authorize('markOwnAttendance');

        $entry = $this->attendance->todayEntry($employee);

        if ($entry === null || $entry->work_mode !== WorkMode::Wfh) {
            return back()->with('error', 'Work from home check-out is only available for WFH sessions.');
        }

        try {
            $this->attendance->checkOut($employee, 0, 0, $request->ip());
        } catch (AttendanceWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Checked out successfully.');
    }
}
