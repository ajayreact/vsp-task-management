<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\AssessmentStatus;
use App\Modules\RecruiterOperations\Http\Requests\AssessmentRequest;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentContentService;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentPresenter;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Assessment authoring (recruiter.assessments.manage): the list, creating an
 * assessment, the builder page for a version, and creating new versions.
 */
class AssessmentController extends Controller
{
    public function __construct(
        protected AssessmentContentService $content,
        protected AssessmentPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $status = AssessmentStatus::tryFrom($request->string('status')->value());
        $search = $request->string('search')->trim()->limit(100, '')->value();

        $rows = Assessment::query()
            ->with(['currentVersion:id,version_number', 'draftVersion:id,assessment_id,version_number'])
            ->withCount('versions')
            ->when($status, fn (Builder $query, AssessmentStatus $value) => $query->where('status', $value->value))
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('title')
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (Assessment $assessment) => [
                ...$this->presenter->assessmentSummary($assessment),
                'current_version' => $assessment->currentVersion?->label(),
                'draft_version' => $assessment->draftVersion?->label(),
                'versions_count' => $assessment->versions_count,
                'assignments_count' => $assessment->current_version_id !== null
                    ? $assessment->currentVersion?->assignments()->count()
                    : 0,
            ]);

        return Inertia::render('RecruiterOperations/assessments/manage/index', [
            'assessments' => $rows,
            'filters' => ['status' => $status->value ?? '', 'search' => $search],
            'statuses' => AssessmentStatus::options(),
            'can' => [
                'create' => $request->user()->can('create', Assessment::class),
                'bank' => $request->user()->can('viewAny', AssessmentQuestion::class),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('RecruiterOperations/assessments/manage/create', [
            'defaults' => [
                'passing_percentage' => AssessmentVersion::DEFAULT_PASSING_PERCENTAGE,
                'max_attempts' => AssessmentVersion::DEFAULT_MAX_ATTEMPTS,
            ],
        ]);
    }

    public function store(AssessmentRequest $request): RedirectResponse
    {
        $assessment = $this->content->createAssessment($request->validated(), $request->user());

        return to_route('recruiter.assessments.manage.show', $assessment)->with('success', 'Assessment created. Add questions to Version 1, then publish it.');
    }

    public function show(Request $request, Assessment $assessment): Response
    {
        $user = $request->user();
        $versions = $assessment->versions()->withCount(['questions', 'assignments'])->orderByDesc('version_number')->get();
        $selected = $versions->firstWhere('id', $request->integer('version'))
            ?? $versions->first(fn (AssessmentVersion $version) => $version->isDraft())
            ?? $versions->firstWhere('id', $assessment->current_version_id)
            ?? $versions->first();

        $questions = $selected?->questions()->with('options')->get() ?? collect();

        return Inertia::render('RecruiterOperations/assessments/manage/show', [
            'assessment' => $this->presenter->assessmentSummary($assessment),
            'versions' => $versions->map(fn (AssessmentVersion $version) => [
                'id' => $version->id,
                'label' => $version->label(),
                'status' => $version->status->value,
                'status_label' => $version->status->label(),
                'questions_count' => $version->questions_count,
                'assignments_count' => $version->assignments_count,
                'published_at' => $version->published_at?->toIso8601String(),
                'is_current' => $version->id === $assessment->current_version_id,
            ])->values()->all(),
            'version' => $selected !== null ? [
                ...$this->presenter->settings($selected),
                'total_points' => (int) $questions->sum('points'),
                'is_current' => $selected->id === $assessment->current_version_id,
                'training_links' => $selected->trainingVersions()->with('course:id,title')->get()
                    ->map(fn ($trainingVersion) => $trainingVersion->course->title.' ('.$trainingVersion->label().')')
                    ->values()->all(),
            ] : null,
            'questions' => $questions->map(fn (AssessmentQuestion $question) => $this->presenter->managerQuestion($question))->values()->all(),
            'can' => [
                'update' => $user->can('update', $assessment),
                'createVersion' => $user->can('createVersion', $assessment),
                'archive' => $user->can('archive', $assessment),
                'restore' => $user->can('restore', $assessment),
                'assign' => $user->can('assign', $assessment),
                'editVersion' => $selected !== null && $user->can('update', $selected),
                'publish' => $selected !== null && $user->can('publish', $selected),
                'archiveVersion' => $selected !== null && $user->can('archive', $selected),
                'discard' => $selected !== null && $user->can('delete', $selected) && $versions->count() > 1,
                'results' => $user->can('viewResults', Assessment::class),
            ],
        ]);
    }

    public function edit(Assessment $assessment): Response
    {
        return Inertia::render('RecruiterOperations/assessments/manage/edit', [
            'assessment' => $this->presenter->assessmentSummary($assessment),
        ]);
    }

    public function update(AssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        /** @var array{title: string, description?: string|null} $data */
        $data = $request->validated();
        $this->content->updateAssessment($assessment, $data, $request->user());

        return to_route('recruiter.assessments.manage.show', $assessment)->with('success', 'Assessment updated.');
    }

    public function archive(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->content->archiveAssessment($assessment, $request->user());

        return back()->with('success', 'Assessment archived. Existing assignments can still be completed.');
    }

    public function restore(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->content->restoreAssessment($assessment, $request->user());

        return back()->with('success', 'Assessment restored.');
    }

    public function storeVersion(Request $request, Assessment $assessment): RedirectResponse
    {
        $source = $request->filled('source_version_id')
            ? AssessmentVersion::query()->find($request->integer('source_version_id'))
            : null;

        $version = $this->content->createVersion($assessment, $request->user(), $source);

        return to_route('recruiter.assessments.manage.show', [$assessment, 'version' => $version->id])
            ->with('success', "Draft {$version->label()} created from the previous version.");
    }
}
