<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceExcelExport;
use App\Modules\Attendance\Services\AttendanceReportService;
use App\Services\AttendanceDashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AttendanceDashboard $dashboard,
        AttendanceReportService $reports,
    ): Response {
        $this->authorize('viewAttendance');

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $employeeId = $request->integer('employee_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;
        $officeId = $request->integer('office_id') ?: null;
        $detailEmployeeId = $request->integer('detail_employee_id') ?: null;

        return Inertia::render('Attendance/dashboard', [
            'snapshot' => $dashboard->snapshot(
                $request->query('status'),
                $request->query('date'),
            ),
            'dailyTable' => $reports->dailyTable(
                $request->query('date'),
                $request->query('status'),
                $employeeId,
                $request->query('search'),
            ),
            'monthlyReport' => $reports->monthlyReport($month, $year, $employeeId, $departmentId, $officeId),
            'employeeDetail' => $detailEmployeeId !== null
                ? $reports->employeeMonthlyDetail($detailEmployeeId, $month, $year)
                : null,
            'filterOptions' => $reports->filterOptions(),
        ]);
    }

    public function exportMonthly(
        Request $request,
        AttendanceExcelExport $export,
    ): StreamedResponse {
        $this->authorize('viewAttendance');

        return $export->monthly(
            (int) $request->query('month', now()->month),
            (int) $request->query('year', now()->year),
            $request->integer('employee_id') ?: null,
            $request->integer('department_id') ?: null,
            $request->integer('office_id') ?: null,
        );
    }
}
