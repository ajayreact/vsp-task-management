<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Exceptions\AttendanceWorkflowException;
use App\Modules\Attendance\Services\AttendanceCheckInOutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceWfhCheckInController extends Controller
{
    public function __construct(protected AttendanceCheckInOutService $attendance) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $employee = $request->user()?->employee;

        abort_if($employee === null, 403);
        $this->authorize('markOwnAttendance');

        try {
            $this->attendance->checkInWfh($employee);
        } catch (AttendanceWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Checked in for work from home.');
    }
}
