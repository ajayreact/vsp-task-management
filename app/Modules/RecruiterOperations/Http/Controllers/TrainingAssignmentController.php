<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Http\Requests\TrainingAssignmentRequest;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Modules\RecruiterOperations\Services\TrainingAssignmentService;
use App\Modules\RecruiterOperations\Services\TrainingPresenter;
use App\Modules\RecruiterOperations\Services\TrainingTrackCatalog;
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
        protected TrainingTrackCatalog $tracks,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrainingAssignment::class);

        $user = $request->user();
        $status = TrainingAssignmentStatus::tryFrom($request->string('status')->value());
        $trackSlug = $request->string('track')->trim()->value();
        $track = $trackSlug !== '' ? $this->tracks->resolve($trackSlug) : null;
        $filters = [
            'track' => $trackSlug,
            'recruiter' => $request->integer('recruiter') ?: null,
            'course' => $request->integer('course') ?: null,
            'status' => $status->value ?? '',
            'due_from' => $this->dateOrNull($request->string('due_from')->trim()->value()) ?? '',
            'due_to' => $this->dateOrNull($request->string('due_to')->trim()->value()) ?? '',
        ];

        $rows = TrainingAssignment::query()
            ->with(['employee:id,user_id,employee_code', 'employee.user:id,name', 'version.course.category', 'version.lessons', 'completions', 'assignedBy:id,name'])
            ->when($trackSlug !== '', fn (Builder $query) => $query->whereHas('version', fn (Builder $version) => $version->whereIn('course_id', TrainingCourse::query()->inTrack($track)->select('id'))))
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
            'courses' => $this->courseOptions(false, $trackSlug !== '' ? $track : false),
            'tracks' => $this->tracks->options(),
            'statuses' => TrainingAssignmentStatus::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', TrainingAssignment::class);

        $courses = $this->courseOptions(true);
        $tracks = $this->tracks->options();
        $requested = collect($courses)->firstWhere('id', $request->integer('course'));
        $requestedTrack = $request->string('track')->value();
        $track = $requested['track'] ?? (collect($tracks)->contains('value', $requestedTrack) ? $requestedTrack : ($tracks[0]['value'] ?? ''));

        return Inertia::render('RecruiterOperations/training/assignments/create', [
            'tracks' => $tracks,
            'courses' => $courses,
            'recruiters' => $this->directory->options(),
            'defaults' => [
                'track' => $track,
                'course_id' => $requested !== null ? (string) $requested['id'] : '',
            ],
            'minDate' => today()->toDateString(),
        ]);
    }

    public function store(TrainingAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $course = TrainingCourse::query()->findOrFail((int) $validated['course_id']);
        $this->tracks->ensureCourseInTrack($course, $request->track());
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
     * Courses with the track they belong to, so the form can list only the
     * chosen track's courses. Pass a track (or null for "not in a track") to
     * limit the list on the server; false means every track.
     *
     * @return list<array{id: int, label: string, track: string, version: string|null}>
     */
    protected function courseOptions(bool $assignableOnly, TrainingTrack|null|false $track = false): array
    {
        return TrainingCourse::query()
            ->with(['currentVersion:id,version_number', 'track:id,slug'])
            ->when($assignableOnly, fn (Builder $query) => $query->assignable()->whereHas('currentVersion.lessons'))
            ->when($track !== false, fn (Builder $query) => $query->inTrack($track ?: null))
            ->orderBy('title')
            ->get(['id', 'title', 'training_track_id', 'current_version_id', 'status'])
            ->map(fn (TrainingCourse $course) => [
                'id' => $course->id,
                'label' => $course->title,
                'track' => $course->track->slug ?? TrainingTrack::UNASSIGNED,
                'version' => $course->currentVersion?->label(),
            ])
            ->values()
            ->all();
    }

    private function dateOrNull(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && strtotime($value) !== false ? $value : null;
    }
}
