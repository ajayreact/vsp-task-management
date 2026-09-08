<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceMarkPayload;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceMarkController extends Controller
{
    public function __construct(protected AttendanceMarkPayload $payload) {}

    public function show(): Response
    {
        $this->authorize('markOwnAttendance');

        $user = request()->user();
        $employee = $user?->employee;
        abort_if($user === null || $employee === null, 403);

        return Inertia::render('Attendance/mark', $this->payload->forEmployee($user, $employee));
    }
}
