<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\AttemptStatus;
use App\Modules\RecruiterOperations\Http\Requests\AssessmentReviewRequest;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAnswer;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentAttemptService;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentPresenter;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentReviewService;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentSummary;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Results and manual review (recruiter.assessments.review). Individual
 * results only: there is no ranking or leaderboard.
 */
class AssessmentResultController extends Controller
{
    public function __construct(
        protected AssessmentAttemptService $attempts,
        protected AssessmentReviewService $reviews,
        protected AssessmentPresenter $presenter,
        protected AssessmentSummary $summary,
        protected RecruiterDirectory $directory,
    ) {}

    public function index(Request $request): Response
    {
        $result = AssessmentResult::tryFrom($request->string('result')->value());
        $filters = [
            'recruiter' => $request->integer('recruiter') ?: null,
            'assessment' => $request->integer('assessment') ?: null,
            'result' => $result->value ?? '',
        ];

        $rows = AssessmentAttempt::query()
            ->with(['version.assessment', 'employee.user:id,name'])
            ->where('status', '!=', AttemptStatus::InProgress->value)
            ->when($filters['recruiter'], fn (Builder $query, int $employee) => $query->where('employee_id', $employee))
            ->when($filters['assessment'], fn (Builder $query, int $assessment) => $query->whereHas('version', fn (Builder $version) => $version->where('assessment_id', $assessment)))
            ->when($result, fn (Builder $query, AssessmentResult $value) => $query->where('result', $value->value))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (AssessmentAttempt $attempt) => [
                'id' => $attempt->id,
                'assessment' => $attempt->version->assessment->title,
                'version' => $attempt->version->label(),
                'recruiter' => $attempt->employee->user->name ?? 'Recruiter',
                'attempt_number' => $attempt->attempt_number,
                'status_label' => $attempt->status->label(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'auto_submitted' => $attempt->auto_submitted,
                'awarded_points' => $attempt->awarded_points,
                'total_points' => $attempt->total_points,
                'percentage' => $attempt->percentage !== null ? (float) $attempt->percentage : null,
                'result' => $attempt->result?->value,
                'result_label' => $attempt->result?->label(),
            ]);

        return Inertia::render('RecruiterOperations/assessments/results/index', [
            'attempts' => $rows,
            'filters' => $filters,
            'recruiters' => $this->directory->options(),
            'assessments' => Assessment::query()->orderBy('title')->get(['id', 'title'])
                ->map(fn (Assessment $assessment) => ['id' => $assessment->id, 'label' => $assessment->title])->values()->all(),
            'results' => AssessmentResult::options(),
            'awaitingReview' => $this->summary->awaitingReviewCount(),
        ]);
    }

    public function show(Request $request, AssessmentAttempt $assessmentAttempt): Response
    {
        $this->attempts->finalizeIfExpired($assessmentAttempt);
        $assessmentAttempt->load(['version.assessment', 'employee.user:id,name']);

        return Inertia::render('RecruiterOperations/assessments/results/show', [
            'assessment' => [
                'id' => $assessmentAttempt->version->assessment->id,
                'title' => $assessmentAttempt->version->assessment->title,
            ],
            'version' => $assessmentAttempt->version->label(),
            'recruiter' => $assessmentAttempt->employee->user->name ?? 'Recruiter',
            'result' => $this->presenter->attemptResult($assessmentAttempt, false),
            'canReview' => $request->user()->can('review', $assessmentAttempt),
        ]);
    }

    public function review(AssessmentReviewRequest $request, AssessmentAttempt $assessmentAttempt, AssessmentAnswer $assessmentAnswer): RedirectResponse
    {
        abort_unless($assessmentAnswer->attempt_id === $assessmentAttempt->id, 404);

        $validated = $request->validated();
        $attempt = $this->reviews->review($assessmentAnswer, (int) $validated['points'], $validated['feedback'] ?? null, $request->user());

        $message = $attempt->result === AssessmentResult::PendingReview
            ? 'Score saved. Other answers in this attempt still need review.'
            : 'Score saved. The attempt result is final.';

        return back()->with('success', $message);
    }
}
