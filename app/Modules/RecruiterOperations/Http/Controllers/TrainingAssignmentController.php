<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Http\Requests\TrainingAssignmentRequest;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Modules\RecruiterOperations\Services\TrainingAssignmentService;
use App\Modules\RecruiterOperations\Services\TrainingPresenter;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Assigning training (recruiter.training.assign): to one recruiter, several,
 * or the whole recruiter team from RecruiterDirectory.
 */
class TrainingAssignmentController extends Controller
{
    public function __construct(
        protected TrainingAssignmentService $assignments,
        protected TrainingPresenter $presenter,
        protected RecruiterDirectory $directory,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrainingAssignment::class);

        $user = $request->user();
        $status = TrainingAssignmentStatus::tryFrom($request->string('status')->value());
        $filters = [
            'recruiter' => $request->integer('recruiter') ?: null,
            'course' => $request->integer('course') ?: null,
            'status' => $status->value ?? '',
            'due_from' => $this->dateOrNull($request->string('due_from')->trim()->value()) ?? '',
            'due_to' => $this->dateOrNull($request->string('due_to')->trim()->value()) ?? '',
        ];

        $rows = TrainingAssignment::query()
            ->with(['employee:id,user_id,employee_code', 'employee.user:id,name', 'version.course.category', 'version.lessons', 'completions', 'assignedBy:id,name'])
            ->when($filters['recruiter'], fn (Builder $query, int $employee) => $query->forEmployee($employee))
            ->when($filters['course'], fn (Builder $query, int $course) => $query->forCourse($course))
            ->when($status, fn (Builder $query, TrainingAssignmentStatus $value) => $query->withEffectiveStatus($value))
            ->when($filters['due_from'], fn (Builder $query, string $from) => $query->where('due_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['due_to'], fn (Builder $query, string $to) => $query->where('due_at', '<=', Carbon::parse($to)->endOfDay()))
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (TrainingAssignment $assignment) => $this->presenter->assignmentRow($assignment, $user));

        return Inertia::render('RecruiterOperations/training/assignments/index', [
            'assignments' => $rows,
            'filters' => $filters,
            'recruiters' => $this->directory->options(),
            'courses' => $this->courseOptions(false),
            'statuses' => TrainingAssignmentStatus::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', TrainingAssignment::class);

        $courses = $this->courseOptions(true);
        $requested = $request->integer('course');

        return Inertia::render('RecruiterOperations/training/assignments/create', [
            'courses' => $courses,
            'recruiters' => $this->directory->options(),
            'defaults' => [
                'course_id' => collect($courses)->contains('id', $requested) ? (string) $requested : '',
            ],
            'minDate' => today()->toDateString(),
        ]);
    }

    public function store(TrainingAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $course = TrainingCourse::query()->findOrFail((int) $validated['course_id']);
        $employeeIds = $validated['mode'] === 'team'
            ? $this->assignments->teamEmployeeIds()
            : array_map('intval', $validated['employee_ids']);
        $dueAt = ! empty($validated['due_at']) ? Carbon::parse($validated['due_at'])->endOfDay() : null;

        $result = $this->assignments->assign($course, $employeeIds, $dueAt, $request->user());

        $created = $result['created']->count();
        $message = $created === 1 ? 'Training assigned to 1 recruiter.' : "Training assigned to {$created} recruiters.";

        if ($result['skipped'] !== []) {
            $names = collect($result['skipped'])->map(fn (array $skip) => "{$skip['name']} ({$skip['reason']})")->implode(', ');
            $message .= ' Skipped: '.$names;
        }

        return to_route('recruiter.training.assignments.index', ['course' => $course->id])->with('success', $message);
    }

    public function destroy(Request $request, TrainingAssignment $trainingAssignment): RedirectResponse
    {
        $this->assignments->unassign($trainingAssignment, $request->user());

        return back()->with('success', 'Training assignment withdrawn.');
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    protected function courseOptions(bool $assignableOnly): array
    {
        return TrainingCourse::query()
            ->with('currentVersion:id,version_number')
            ->when($assignableOnly, fn (Builder $query) => $query->assignable())
            ->orderBy('title')
            ->get(['id', 'title', 'current_version_id', 'status'])
            ->map(fn (TrainingCourse $course) => [
                'id' => $course->id,
                'label' => $course->title.($course->currentVersion !== null ? ' · '.$course->currentVersion->label() : ''),
            ])
            ->values()
            ->all();
    }

    private function dateOrNull(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && strtotime($value) !== false ? $value : null;
    }
}
