<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AttendanceDashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceDashboardController extends Controller
{
    public function __invoke(Request $request, AttendanceDashboard $dashboard): Response
    {
        $this->authorize('viewAttendance');

        return Inertia::render('Attendance/dashboard', [
            'snapshot' => $dashboard->snapshot(
                $request->query('status'),
                $request->query('date'),
            ),
        ]);
    }
}
