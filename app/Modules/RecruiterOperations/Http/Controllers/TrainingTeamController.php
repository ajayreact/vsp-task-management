<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Modules\RecruiterOperations\Services\TrainingPresenter;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Team training progress for recruiter.team.view. Learning progress only; no
 * scores and no rankings.
 */
class TrainingTeamController extends Controller
{
    public function __construct(
        protected TrainingPresenter $presenter,
        protected RecruiterDirectory $directory,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewTeam', TrainingCourse::class);

        $user = $request->user();
        $status = TrainingAssignmentStatus::tryFrom($request->string('status')->value());
        $filters = [
            'recruiter' => $request->integer('recruiter') ?: null,
            'course' => $request->integer('course') ?: null,
            'status' => $status->value ?? '',
        ];

        $assignments = TrainingAssignment::query()
            ->with(['employee:id,user_id,employee_code', 'employee.user:id,name', 'version.course.category', 'version.lessons', 'completions', 'assignedBy:id,name'])
            ->when($filters['recruiter'], fn (Builder $query, int $employee) => $query->forEmployee($employee))
            ->when($filters['course'], fn (Builder $query, int $course) => $query->forCourse($course))
            ->when($status, fn (Builder $query, TrainingAssignmentStatus $value) => $query->withEffectiveStatus($value))
            ->orderByRaw('case when status = ? then 1 else 0 end', [TrainingAssignmentStatus::Completed->value])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (TrainingAssignment $assignment) => $this->presenter->assignmentRow($assignment, $user));

        return Inertia::render('RecruiterOperations/training/team', [
            'assignments' => $assignments,
            'filters' => $filters,
            'recruiters' => $this->directory->options(),
            'courses' => TrainingCourse::query()->orderBy('title')->get(['id', 'title'])
                ->map(fn (TrainingCourse $course) => ['id' => $course->id, 'label' => $course->title])->values()->all(),
            'statuses' => TrainingAssignmentStatus::options(),
        ]);
    }
}
