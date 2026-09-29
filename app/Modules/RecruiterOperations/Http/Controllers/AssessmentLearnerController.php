<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Http\Requests\AssessmentAnswersRequest;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentAttemptService;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentPresenter;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A recruiter's own quizzes: the list, an assignment page, taking an attempt
 * and seeing the result. Pages never carry the answer key before submission;
 * scores and deadlines come from the server only.
 */
class AssessmentLearnerController extends Controller
{
    public function __construct(
        protected AssessmentAttemptService $attempts,
        protected AssessmentPresenter $presenter,
        protected AssessmentSummary $summary,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $employee = $this->employee($user);
        $assignments = $employee !== null ? $this->summary->assignmentsFor($employee) : collect();

        foreach ($assignments as $assignment) {
            $this->attempts->sweep($assignment);
        }

        return Inertia::render('RecruiterOperations/assessments/index', [
            'hasEmployeeProfile' => $employee !== null,
            'counts' => $employee !== null ? $this->summary->counts($assignments) : null,
            'assignments' => $assignments
                ->map(fn (AssessmentAssignment $assignment) => $this->presenter->assignment($assignment, $this->attempts->eligibility($assignment)))
                ->values()
                ->all(),
            'can' => $this->abilities($user),
        ]);
    }

    public function show(Request $request, AssessmentAssignment $assessmentAssignment): Response
    {
        $assessmentAssignment->load(['version.assessment', 'attempts']);
        $this->attempts->sweep($assessmentAssignment);
        $assessmentAssignment->unsetRelation('attempts')->load('attempts');

        return Inertia::render('RecruiterOperations/assessments/show', [
            'assignment' => $this->presenter->assignment($assessmentAssignment, $this->attempts->eligibility($assessmentAssignment)),
            'attempts' => $assessmentAssignment->attempts
                ->map(fn (AssessmentAttempt $attempt) => $this->presenter->attemptResult($attempt, true))
                ->map(fn (array $attempt) => [...$attempt, 'questions' => []])
                ->values()
                ->all(),
        ]);
    }

    public function start(Request $request, AssessmentAssignment $assessmentAssignment): RedirectResponse
    {
        $attempt = $this->attempts->start($assessmentAssignment, $request->user());

        return to_route('recruiter.assessments.attempts.show', $attempt);
    }

    public function attempt(Request $request, AssessmentAttempt $assessmentAttempt): Response
    {
        $this->attempts->finalizeIfExpired($assessmentAttempt);
        $assessmentAttempt->load(['version.assessment', 'assignment']);

        $context = [
            'assessment' => [
                'id' => $assessmentAttempt->version->assessment->id,
                'title' => $assessmentAttempt->version->assessment->title,
            ],
            'assignmentId' => $assessmentAttempt->assignment_id,
            'version' => [
                'label' => $assessmentAttempt->version->label(),
                'instructions' => $assessmentAttempt->version->instructions,
                'time_limit_minutes' => $assessmentAttempt->version->time_limit_minutes,
                'passing_percentage' => $assessmentAttempt->version->passing_percentage,
            ],
        ];

        if ($assessmentAttempt->isInProgress()) {
            return Inertia::render('RecruiterOperations/assessments/attempt', [
                ...$context,
                'attempt' => [
                    'id' => $assessmentAttempt->id,
                    'attempt_number' => $assessmentAttempt->attempt_number,
                    'started_at' => $assessmentAttempt->started_at->toIso8601String(),
                    'expires_at' => $assessmentAttempt->expires_at?->toIso8601String(),
                    'seconds_remaining' => $assessmentAttempt->secondsRemaining(),
                ],
                'questions' => $this->presenter->learnerQuestions($assessmentAttempt),
                'urls' => [
                    'save' => route('recruiter.assessments.attempts.save', $assessmentAttempt),
                    'submit' => route('recruiter.assessments.attempts.submit', $assessmentAttempt),
                ],
            ]);
        }

        $eligibility = $this->attempts->eligibility($assessmentAttempt->assignment()->with(['version', 'attempts'])->firstOrFail());

        return Inertia::render('RecruiterOperations/assessments/result', [
            ...$context,
            'result' => $this->presenter->attemptResult($assessmentAttempt, true),
            'eligibility' => $eligibility,
        ]);
    }

    public function save(AssessmentAnswersRequest $request, AssessmentAttempt $assessmentAttempt): JsonResponse
    {
        $attempt = $this->attempts->saveAnswers($assessmentAttempt, $request->answers(), $request->user());

        return response()->json([
            'saved_at' => now()->toIso8601String(),
            'seconds_remaining' => $attempt->secondsRemaining(),
        ]);
    }

    public function submit(AssessmentAnswersRequest $request, AssessmentAttempt $assessmentAttempt): RedirectResponse
    {
        $attempt = $this->attempts->submit($assessmentAttempt, $request->answers(), $request->user());

        $message = $attempt->auto_submitted
            ? 'Time was up, so your attempt was submitted with the answers saved before the deadline.'
            : 'Your answers have been submitted.';

        return to_route('recruiter.assessments.attempts.show', $attempt)->with('success', $message);
    }

    protected function employee(User $user): ?Employee
    {
        return Employee::query()->where('user_id', $user->id)->first();
    }

    /**
     * @return array{manage: bool, assign: bool, review: bool, bank: bool}
     */
    protected function abilities(User $user): array
    {
        return [
            'manage' => $user->can('viewAny', Assessment::class),
            'assign' => $user->can('viewAny', AssessmentAssignment::class),
            'review' => $user->can('viewResults', Assessment::class),
            'bank' => $user->can('viewAny', AssessmentQuestion::class),
        ];
    }
}
