<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Http\Requests\TrainingProgressRequest;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Modules\RecruiterOperations\Services\TrainingPresenter;
use App\Modules\RecruiterOperations\Services\TrainingProgressService;
use App\Modules\RecruiterOperations\Services\TrainingSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A recruiter's own training: the dashboard, My Training, course and lesson
 * pages, and recording lesson progress. Everything is resolved from the
 * signed-in person's own employee record, so there is no way to open someone
 * else's assignment from here.
 */
class TrainingLearnerController extends Controller
{
    public function __construct(
        protected TrainingProgressService $progress,
        protected TrainingSummary $summary,
        protected TrainingPresenter $presenter,
        protected RecruiterDirectory $directory,
    ) {}

    public function dashboard(Request $request): Response
    {
        $this->authorize('viewAny', TrainingCourse::class);

        $user = $request->user();
        $isLearner = $this->directory->isTrainingLearner($user);
        $employee = $isLearner ? $this->employee($user) : null;
        $assignments = $employee !== null ? $this->summary->assignmentsFor($employee) : collect();

        return Inertia::render('RecruiterOperations/training/dashboard', [
            'isLearner' => $isLearner,
            'hasEmployeeProfile' => $employee !== null,
            'counts' => $employee !== null ? $this->summary->counts($assignments) : null,
            'continueLearning' => $assignments
                ->filter(fn (TrainingAssignment $assignment) => ! $assignment->isCompleted())
                ->take(5)
                ->map(fn (TrainingAssignment $assignment) => $this->presenter->learnerCard($assignment))
                ->values()
                ->all(),
            'can' => $this->abilities($user),
        ]);
    }

    public function myTraining(Request $request): Response
    {
        $this->authorize('viewAny', TrainingCourse::class);

        $user = $request->user();
        $employee = $this->employee($user);
        $status = TrainingAssignmentStatus::tryFrom($request->string('status')->value());

        $cards = $employee !== null
            ? $this->summary->assignmentsFor($employee)
                ->filter(fn (TrainingAssignment $assignment) => $status === null || $assignment->effectiveStatus() === $status)
                ->map(fn (TrainingAssignment $assignment) => $this->presenter->learnerCard($assignment))
                ->values()
                ->all()
            : [];

        return Inertia::render('RecruiterOperations/training/my-training', [
            'hasEmployeeProfile' => $employee !== null,
            'assignments' => $cards,
            'filters' => ['status' => $status->value ?? ''],
            'statuses' => TrainingAssignmentStatus::options(),
            'can' => $this->abilities($user),
        ]);
    }

    public function course(Request $request, TrainingCourse $trainingCourse): Response
    {
        $this->authorize('viewAny', TrainingCourse::class);

        $employee = $this->employee($request->user());
        $assignment = $this->assignmentOr404($trainingCourse, $employee);
        $assignment->load(['version.lessons.media', 'completions']);

        $progress = $this->progress->progressFor($assignment);
        $completions = $assignment->completions->keyBy('lesson_id');
        $resume = $this->progress->resumeLesson($assignment);
        $status = $assignment->effectiveStatus();

        return Inertia::render('RecruiterOperations/training/course', [
            'course' => [
                'id' => $trainingCourse->id,
                'title' => $trainingCourse->title,
                'description' => $trainingCourse->description,
                'category' => $trainingCourse->category->name ?? null,
            ],
            'version' => [
                'label' => $assignment->version->label(),
                'description' => $assignment->version->description,
                'estimated_minutes' => $assignment->version->estimatedMinutes(),
            ],
            'assignment' => [
                'status' => $status->value,
                'status_label' => $status->label(),
                'assigned_at' => $assignment->assigned_at->toIso8601String(),
                'due_at' => $assignment->due_at?->toIso8601String(),
                'started_at' => $assignment->started_at?->toIso8601String(),
                'completed_at' => $assignment->completed_at?->toIso8601String(),
            ],
            'progress' => [
                'percent' => $progress['percent'],
                'completed' => $progress['completed'],
                'counted' => $progress['counted'],
                'total' => $progress['total'],
            ],
            'lessons' => $assignment->version->lessons->map(function (TrainingLesson $lesson) use ($completions) {
                /** @var TrainingLessonCompletion|null $completion */
                $completion = $completions->get($lesson->id);

                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'content_type' => $lesson->content_type->value,
                    'content_type_label' => $lesson->content_type->label(),
                    'duration_minutes' => $lesson->duration_minutes,
                    'is_required' => $lesson->is_required,
                    'started' => $completion?->started_at !== null,
                    'completed' => $completion?->completed_at !== null,
                ];
            })->values()->all(),
            'resumeLessonId' => $resume?->id,
            'history' => $this->history($trainingCourse, $employee, $assignment),
            'quizzes' => $this->quizzes($assignment),
        ]);
    }

    public function lesson(Request $request, TrainingCourse $trainingCourse, TrainingLesson $trainingLesson): Response
    {
        $this->authorize('viewAny', TrainingCourse::class);

        $user = $request->user();
        $assignment = $this->assignmentOr404($trainingCourse, $this->employee($user));
        abort_unless($trainingLesson->course_version_id === $assignment->course_version_id, 404);

        $completion = $this->progress->startLesson($assignment, $trainingLesson, $user);

        $assignment->refresh()->load(['version.lessons', 'completions']);
        $lessons = $assignment->version->lessons->values();
        $index = $lessons->search(fn (TrainingLesson $lesson) => $lesson->id === $trainingLesson->id);
        $index = is_int($index) ? $index : 0;
        $previous = $lessons->get($index - 1);
        $next = $lessons->get($index + 1);
        $completed = $this->progress->completedLessonIds($assignment);
        $progress = $this->progress->progressFor($assignment);

        return Inertia::render('RecruiterOperations/training/lesson', [
            'course' => ['id' => $trainingCourse->id, 'title' => $trainingCourse->title],
            'version' => $assignment->version->label(),
            'lesson' => $this->presenter->lesson($trainingLesson),
            'position' => ['index' => $index + 1, 'total' => $lessons->count()],
            'completion' => [
                'completed' => $completion->completed_at !== null,
                'completed_at' => $completion->completed_at?->toIso8601String(),
                'audio_progress_seconds' => (int) $completion->audio_progress_seconds,
                'time_spent_seconds' => (int) $completion->time_spent_seconds,
            ],
            'previousLesson' => $index > 0 && $previous !== null ? ['id' => $previous->id, 'title' => $previous->title] : null,
            'nextLesson' => $next !== null ? ['id' => $next->id, 'title' => $next->title] : null,
            'outline' => $lessons->map(fn (TrainingLesson $lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'is_required' => $lesson->is_required,
                'completed' => in_array($lesson->id, $completed, true),
            ])->all(),
            'progress' => ['percent' => $progress['percent'], 'completed' => $progress['completed'], 'counted' => $progress['counted']],
            'courseCompleted' => $assignment->isCompleted(),
            'audio' => $this->presenter->audio($trainingLesson),
            'urls' => [
                'complete' => route('recruiter.training.lessons.complete', [$trainingCourse, $trainingLesson]),
                'progress' => route('recruiter.training.lessons.progress', [$trainingCourse, $trainingLesson]),
            ],
        ]);
    }

    public function complete(Request $request, TrainingCourse $trainingCourse, TrainingLesson $trainingLesson): RedirectResponse
    {
        $this->authorize('viewAny', TrainingCourse::class);

        $user = $request->user();
        $assignment = $this->assignmentOr404($trainingCourse, $this->employee($user));
        abort_unless($trainingLesson->course_version_id === $assignment->course_version_id, 404);

        $wasCompleted = $assignment->isCompleted();
        $this->progress->completeLesson($assignment, $trainingLesson, $user);
        $assignment->refresh();

        if (! $wasCompleted && $assignment->isCompleted()) {
            return to_route('recruiter.training.courses.show', $trainingCourse)
                ->with('success', 'Course completed. Well done!');
        }

        $next = $assignment->version->lessons()
            ->where(fn ($query) => $query
                ->where('sort_order', '>', $trainingLesson->sort_order)
                ->orWhere(fn ($same) => $same->where('sort_order', $trainingLesson->sort_order)->where('id', '>', $trainingLesson->id)))
            ->first();

        if ($next !== null) {
            return to_route('recruiter.training.lessons.show', [$trainingCourse, $next])
                ->with('success', 'Lesson marked complete.');
        }

        return to_route('recruiter.training.courses.show', $trainingCourse)->with('success', 'Lesson marked complete.');
    }

    public function progress(TrainingProgressRequest $request, TrainingCourse $trainingCourse, TrainingLesson $trainingLesson): JsonResponse
    {
        $this->authorize('viewAny', TrainingCourse::class);

        $user = $request->user();
        $assignment = $this->assignmentOr404($trainingCourse, $this->employee($user));
        abort_unless($trainingLesson->course_version_id === $assignment->course_version_id, 404);

        $validated = $request->validated();
        $completion = $this->progress->recordProgress(
            $assignment,
            $trainingLesson,
            $user,
            isset($validated['audio_seconds']) ? (int) $validated['audio_seconds'] : null,
            isset($validated['spent_seconds']) ? (int) $validated['spent_seconds'] : null,
        );

        return response()->json([
            'audio_progress_seconds' => (int) $completion->audio_progress_seconds,
            'time_spent_seconds' => (int) $completion->time_spent_seconds,
            'completed' => $completion->completed_at !== null,
        ]);
    }

    protected function employee(User $user): ?Employee
    {
        return Employee::query()->where('user_id', $user->id)->first();
    }

    protected function assignmentOr404(TrainingCourse $course, ?Employee $employee): TrainingAssignment
    {
        $assignment = $this->progress->learnerAssignment($course, $employee);
        abort_if($assignment === null, 404);

        return $assignment;
    }

    /**
     * Earlier versions of this course the recruiter completed.
     *
     * @return list<array{version: string, completed_at: string|null}>
     */
    protected function history(TrainingCourse $course, ?Employee $employee, TrainingAssignment $current): array
    {
        if ($employee === null) {
            return [];
        }

        return TrainingAssignment::query()
            ->forEmployee($employee)
            ->forCourse($course)
            ->whereKeyNot($current->id)
            ->where('status', TrainingAssignmentStatus::Completed->value)
            ->with('version:id,version_number')
            ->orderByDesc('completed_at')
            ->get()
            ->map(fn (TrainingAssignment $assignment) => [
                'version' => $assignment->version->label(),
                'completed_at' => $assignment->completed_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * The recruiter's own assignments for the quizzes linked to this course
     * version. Status only; never questions or answers.
     *
     * @return list<array{id: int, title: string, status_label: string, result: string|null, result_label: string|null}>
     */
    protected function quizzes(TrainingAssignment $assignment): array
    {
        return AssessmentAssignment::query()
            ->where('employee_id', $assignment->employee_id)
            ->whereIn('assessment_version_id', $assignment->version->assessmentVersions()->pluck('ro_assessment_versions.id'))
            ->with('version.assessment:id,title')
            ->get()
            ->map(function (AssessmentAssignment $quiz) {
                $showOutcome = $quiz->version->show_result || $quiz->result === AssessmentResult::PendingReview;

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->version->assessment->title,
                    'status_label' => $quiz->effectiveStatus()->label(),
                    'result' => $showOutcome ? $quiz->result?->value : null,
                    'result_label' => $showOutcome ? $quiz->result?->label() : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{manage: bool, assign: bool, viewTeam: bool}
     */
    protected function abilities(User $user): array
    {
        return [
            'manage' => $user->can('manage', TrainingCourse::class),
            'assign' => $user->can('assign', TrainingCourse::class),
            'viewTeam' => $user->can('viewTeam', TrainingCourse::class),
        ];
    }
}
