<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Enums\AttendanceAction;
use App\Modules\Attendance\Http\Requests\VerifyAttendanceLocationRequest;
use App\Modules\Attendance\Services\AttendanceLocationVerificationService;
use Illuminate\Http\JsonResponse;

class AttendanceLocationVerificationController extends Controller
{
    public function __construct(
        protected AttendanceLocationVerificationService $verification,
    ) {}

    public function store(VerifyAttendanceLocationRequest $request): JsonResponse
    {
        $employee = $request->user()?->employee;

        abort_if($employee === null, 403);

        $result = $this->verification->verify(
            $employee,
            AttendanceAction::from($request->validated('action')),
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            $request->ip(),
        );

        $status = $result->passed ? 200 : 422;

        return response()->json($result->toArray(), $status);
    }
}
