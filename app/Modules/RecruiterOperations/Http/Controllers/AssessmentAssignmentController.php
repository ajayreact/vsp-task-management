<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\AssessmentAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Http\Requests\AssessmentAssignmentRequest;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentAssignmentService;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentPresenter;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Assigning assessments (recruiter.assessments.invite): to one recruiter,
 * several, or the whole recruiter team from RecruiterDirectory.
 */
class AssessmentAssignmentController extends Controller
{
    public function __construct(
        protected AssessmentAssignmentService $assignments,
        protected AssessmentPresenter $presenter,
        protected RecruiterDirectory $directory,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $status = AssessmentAssignmentStatus::tryFrom($request->string('status')->value());
        $result = AssessmentResult::tryFrom($request->string('result')->value());
        $filters = [
            'recruiter' => $request->integer('recruiter') ?: null,
            'assessment' => $request->integer('assessment') ?: null,
            'status' => $status->value ?? '',
            'result' => $result->value ?? '',
        ];

        $rows = AssessmentAssignment::query()
            ->with(['version.assessment', 'employee.user:id,name', 'assignedBy:id,name', 'attempts'])
            ->when($filters['recruiter'], fn (Builder $query, int $employee) => $query->forEmployee($employee))
            ->when($filters['assessment'], fn (Builder $query, int $assessment) => $query->forAssessment($assessment))
            ->when($status, fn (Builder $query, AssessmentAssignmentStatus $value) => $query->withEffectiveStatus($value))
            ->when($result, fn (Builder $query, AssessmentResult $value) => $query->where('result', $value->value))
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (AssessmentAssignment $assignment) => [
                ...$this->presenter->assignment($assignment),
                'can_delete' => $user->can('delete', $assignment),
            ]);

        return Inertia::render('RecruiterOperations/assessments/assignments/index', [
            'assignments' => $rows,
            'filters' => $filters,
            'recruiters' => $this->directory->options(),
            'assessments' => $this->assessmentOptions(false),
            'statuses' => AssessmentAssignmentStatus::options(),
            'results' => AssessmentResult::options(),
            'can' => ['create' => $user->can('create', AssessmentAssignment::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $assessments = $this->assessmentOptions(true);
        $requested = $request->integer('assessment');

        return Inertia::render('RecruiterOperations/assessments/assignments/create', [
            'assessments' => $assessments,
            'recruiters' => $this->directory->options(),
            'defaults' => [
                'assessment_id' => collect($assessments)->contains('id', $requested) ? (string) $requested : '',
            ],
            'minDate' => today()->toDateString(),
        ]);
    }

    public function store(AssessmentAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $assessment = Assessment::query()->findOrFail((int) $validated['assessment_id']);
        $employeeIds = $validated['mode'] === 'team'
            ? $this->assignments->teamEmployeeIds()
            : array_map('intval', $validated['employee_ids']);
        $dueAt = ! empty($validated['due_at']) ? Carbon::parse($validated['due_at'])->endOfDay() : null;

        $result = $this->assignments->assign($assessment, $employeeIds, $dueAt, $request->user());

        $created = $result['created']->count();
        $message = $created === 1 ? 'Assessment assigned to 1 recruiter.' : "Assessment assigned to {$created} recruiters.";

        if ($result['skipped'] !== []) {
            $names = collect($result['skipped'])->map(fn (array $skip) => "{$skip['name']} ({$skip['reason']})")->implode(', ');
            $message .= ' Skipped: '.$names;
        }

        return to_route('recruiter.assessments.assignments.index', ['assessment' => $assessment->id])->with('success', $message);
    }

    public function destroy(Request $request, AssessmentAssignment $assessmentAssignment): RedirectResponse
    {
        $this->assignments->unassign($assessmentAssignment, $request->user());

        return back()->with('success', 'Assignment withdrawn.');
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    protected function assessmentOptions(bool $assignableOnly): array
    {
        return Assessment::query()
            ->with('currentVersion:id,version_number')
            ->when($assignableOnly, fn (Builder $query) => $query->assignable())
            ->orderBy('title')
            ->get(['id', 'title', 'current_version_id', 'status', 'type'])
            ->map(fn (Assessment $assessment) => [
                'id' => $assessment->id,
                'label' => $assessment->title.($assessment->currentVersion !== null ? ' · '.$assessment->currentVersion->label() : ''),
            ])
            ->values()
            ->all();
    }
}
