<?php

namespace App\Http\Controllers\RecruiterOperations;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentSummary;
use App\Modules\RecruiterOperations\Services\RecruiterDailyActivitySummary;
use App\Modules\RecruiterOperations\Services\RecruiterTaskSummary;
use App\Modules\RecruiterOperations\Services\TrainingSummary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Recruiter Operations home. Lives outside `App\Modules` so that it can later
 * compose Attendance data without the module importing Attendance.
 */
class RecruiterDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        RecruiterTaskSummary $tasks,
        RecruiterDailyActivitySummary $activities,
        TrainingSummary $training,
        AssessmentSummary $assessments,
    ): Response {
        $user = $request->user();
        abort_if($user === null, 403);

        $employee = $user->employee;

        return Inertia::render('RecruiterOperations/dashboard', [
            'hasEmployeeProfile' => $employee !== null,
            'myTasks' => $employee !== null ? $tasks->forEmployee($employee) : null,
            'teamTasks' => $user->can('viewTeam', RecruiterTask::class) ? $tasks->forTeam() : null,
            'todayActivities' => $employee !== null ? $activities->todayFor($employee) : null,
            'training' => $employee !== null ? $training->countsFor($employee) : null,
            'assessments' => $employee !== null ? $assessments->countsFor($employee) : null,
            'awaitingReview' => $user->can('viewResults', Assessment::class) ? $assessments->awaitingReviewCount() : null,
        ]);
    }
}
