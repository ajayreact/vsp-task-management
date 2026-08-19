<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Exceptions\AttendanceWorkflowException;
use App\Modules\Attendance\Services\AttendanceBreakService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceBreakResumeController extends Controller
{
    public function __construct(protected AttendanceBreakService $breaks) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $this->authorize('markOwnAttendance');

        $employee = $request->user()?->employee;

        abort_if($employee === null, 403);

        try {
            $this->breaks->resumeWork($employee);
        } catch (AttendanceWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Break ended. You are working again.');
    }
}
