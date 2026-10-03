<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\TrainingComplianceStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentReview;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Http\Requests\TrainingComplianceReviewRequest;
use App\Modules\RecruiterOperations\Http\Requests\TrainingContentReviewRequest;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingContentReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The review queue for draft lesson content: a dashboard of review states,
 * a side-by-side review page, and the reviewer's decisions. Only draft
 * versions are listed; published content is never reviewed or changed here.
 */
class TrainingContentReviewController extends Controller
{
    public function __construct(protected TrainingContentReviewService $reviews) {}

    public function index(Request $request): Response
    {
        $language = TrainingLanguage::tryFrom($request->string('language')->value()) ?? TrainingLanguage::default();
        $lessons = $this->reviews->draftLessons();

        $rows = $this->reviews->queue($lessons, $language)->map(function (TrainingLesson $lesson) use ($language) {
            $state = $this->reviews->languageState($lesson, $language);
            $compliance = $lesson->compliance();

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'course' => ['id' => $lesson->version->course->id, 'title' => $lesson->version->course->title],
                'level' => $lesson->version->course->category->level_number ?? null,
                'version' => $lesson->version->label(),
                'status' => $state['status'] ?? TrainingContentReview::NeedsReview->value,
                'status_label' => $state['label'] ?? TrainingContentReview::NeedsReview->labelFor($language),
                'words' => $state['words'] ?? 0,
                'over_target' => $state['over_target'] ?? false,
                'compliance' => ['status' => $compliance->value, 'label' => $compliance->label(), 'required' => $compliance->isRequired()],
                'updated_at' => $state['updated_at'] ?? null,
                'reviewer' => $state['reviewer'] ?? null,
            ];
        })->values();

        return Inertia::render('RecruiterOperations/training/manage/review/index', [
            'language' => $language->value,
            'languages' => array_map(fn (TrainingLanguage $case) => ['code' => $case->value, 'label' => $case->label()], TrainingLanguage::cases()),
            'summary' => $this->reviews->summary($lessons),
            'rows' => $rows,
            'statuses' => TrainingContentReview::options(),
            'complianceStatuses' => TrainingComplianceStatus::options(),
            'overTargetWords' => TrainingContentReviewService::OVER_TARGET_WORDS,
        ]);
    }

    public function show(Request $request, TrainingLesson $trainingLesson, string $language): Response
    {
        $locale = TrainingLanguage::tryFrom($language);
        abort_if($locale === null, 404);

        $queue = $this->reviews->queue($this->reviews->draftLessons(), $locale);
        $position = $queue->search(fn (TrainingLesson $lesson) => $lesson->id === $trainingLesson->id);
        abort_if($position === false, 404);

        /** @var TrainingLesson $lesson */
        $lesson = $queue[$position];
        $previous = $position > 0 ? $queue[$position - 1] : null;
        $next = $queue[$position + 1] ?? null;
        $compliance = $lesson->compliance();

        return Inertia::render('RecruiterOperations/training/manage/review/show', [
            'language' => $locale->value,
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'course' => ['id' => $lesson->version->course->id, 'title' => $lesson->version->course->title],
                'level' => $lesson->version->course->category->level_number ?? null,
                'version' => ['id' => $lesson->version->id, 'label' => $lesson->version->label()],
                'english' => $this->reviews->languageState($lesson, TrainingLanguage::English),
                'translation' => $locale->isCanonical() ? null : $this->reviews->languageState($lesson, $locale),
                'translation_label' => $locale->isCanonical() ? null : $locale->label(),
                'compliance' => [
                    'status' => $compliance->value,
                    'label' => $compliance->label(),
                    'required' => $compliance->isRequired(),
                    'reviewer' => $lesson->complianceReviewer?->name,
                    'reviewed_at' => $lesson->compliance_reviewed_at?->toIso8601String(),
                    'note' => $lesson->compliance_note,
                ],
            ],
            'navigation' => [
                'position' => $position + 1,
                'total' => $queue->count(),
                'previous' => $previous === null ? null : ['id' => $previous->id, 'title' => $previous->title],
                'next' => $next === null ? null : ['id' => $next->id, 'title' => $next->title],
            ],
            'overTargetWords' => TrainingContentReviewService::OVER_TARGET_WORDS,
            'can' => ['review' => $request->user()->can('update', $lesson)],
        ]);
    }

    public function updateStatus(TrainingContentReviewRequest $request, TrainingLesson $trainingLesson, string $language): RedirectResponse
    {
        $locale = TrainingLanguage::tryFrom($language);
        abort_if($locale === null, 404);

        $status = TrainingContentReview::from((string) $request->validated('status'));
        $this->reviews->setStatus($trainingLesson, $locale, $status, $request->user(), $request->validated('note'));

        $target = $trainingLesson;

        if ($request->boolean('next')) {
            $queue = $this->reviews->queue($this->reviews->draftLessons(), $locale);
            $position = $queue->search(fn (TrainingLesson $lesson) => $lesson->id === $trainingLesson->id);
            $target = $position === false ? $trainingLesson : ($queue[$position + 1] ?? $trainingLesson);
        }

        return to_route('recruiter.training.manage.review.show', [$target, $locale->value])
            ->with('success', $locale->label().': '.mb_strtolower($status->labelFor($locale)).' — '.$trainingLesson->title.'.');
    }

    public function updateCompliance(TrainingComplianceReviewRequest $request, TrainingLesson $trainingLesson): RedirectResponse
    {
        $status = TrainingComplianceStatus::from((string) $request->validated('status'));
        $this->reviews->setCompliance($trainingLesson, $status, $request->user(), $request->validated('note'));

        return back()->with('success', 'Compliance: '.mb_strtolower($status->label()).'.');
    }
}
