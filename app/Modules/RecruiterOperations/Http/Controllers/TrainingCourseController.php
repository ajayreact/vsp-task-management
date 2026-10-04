<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Http\Requests\TrainingCourseRequest;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonContent;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingTrackCatalog;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Course authoring for recruiter.training.manage: the course list and the
 * course page, which always shows the course's live copy. Saved changes reach
 * every assigned recruiter straight away; older versions are history only.
 */
class TrainingCourseController extends Controller
{
    public function __construct(
        protected TrainingContentService $content,
        protected TrainingTrackCatalog $tracks,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('manage', TrainingCourse::class);

        $user = $request->user();
        $status = TrainingContentStatus::tryFrom($request->string('status')->value());
        $tracks = $this->tracks->options();
        $trackSlug = $request->string('track')->trim()->value() ?: ($tracks[0]['value'] ?? TrainingTrack::UNASSIGNED);
        $track = $this->tracks->resolve($trackSlug);
        $filters = [
            'track' => $trackSlug,
            'category' => $request->integer('category') ?: null,
            'status' => $status->value ?? '',
            'search' => $request->string('search')->trim()->limit(100, '')->value(),
        ];

        $courses = TrainingCourse::query()
            ->select('ro_training_courses.*')
            ->leftJoin('ro_training_categories as c', 'c.id', '=', 'ro_training_courses.category_id')
            ->with([
                'category:id,name,level_number',
                'currentVersion:id,estimated_minutes',
                'currentVersion.lessons:id,course_version_id,body,duration_minutes',
                'draftVersion:id,course_id,estimated_minutes',
                'draftVersion.lessons:id,course_version_id,body,duration_minutes',
            ])
            ->addSelect(['assignments_count' => TrainingAssignment::query()
                ->selectRaw('count(*)')
                ->join('ro_training_course_versions as v', 'v.id', '=', 'ro_training_assignments.course_version_id')
                ->whereColumn('v.course_id', 'ro_training_courses.id')])
            ->inTrack($track)
            ->when($filters['category'], fn (Builder $query, int $category) => $query->where('ro_training_courses.category_id', $category))
            ->when($status, fn (Builder $query, TrainingContentStatus $value) => $query->where('ro_training_courses.status', $value->value))
            ->when($filters['search'] !== '', fn (Builder $query) => $query->where('ro_training_courses.title', 'like', '%'.$filters['search'].'%'))
            ->orderBy('c.sort_order')
            ->orderBy('c.level_number')
            ->orderBy('ro_training_courses.title')
            ->paginate(Pagination::perPage($request, 20))
            ->withQueryString()
            ->through(fn (TrainingCourse $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'category' => $course->category->name ?? null,
                'status' => $course->status->value,
                'status_label' => $course->status->label(),
                'duration_minutes' => ($course->currentVersion ?? $course->draftVersion)?->estimatedMinutes(),
                'assignments_count' => (int) $course->getAttribute('assignments_count'),
            ]);

        return Inertia::render('RecruiterOperations/training/manage/index', [
            'courses' => $courses,
            'filters' => $filters,
            'tracks' => $tracks,
            'trackName' => $track->name ?? TrainingTrackCatalog::UNASSIGNED_NAME,
            'categories' => $this->categoryOptions(false),
            'statuses' => TrainingContentStatus::options(),
            'can' => [
                'create' => $user->can('create', TrainingCourse::class),
                'assign' => $user->can('assign', TrainingCourse::class),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', TrainingCourse::class);

        $tracks = $this->tracks->formOptions();
        $requestedTrack = TrainingTrack::query()->active()->where('slug', $request->string('track')->value())->value('id');

        return Inertia::render('RecruiterOperations/training/manage/courses/create', [
            'categories' => $this->categoryOptions(true),
            'tracks' => $tracks,
            'defaults' => [
                'category_id' => (string) ($request->integer('category') ?: ''),
                'training_track_id' => (string) ($requestedTrack ?? ($tracks[0]['id'] ?? '')),
            ],
        ]);
    }

    public function store(TrainingCourseRequest $request): RedirectResponse
    {
        $course = $this->content->createCourse($request->validated(), $request->user());

        return to_route('recruiter.training.manage.courses.show', $course)
            ->with('success', 'Course created. Add lessons; every change is live as soon as you save it.');
    }

    public function show(Request $request, TrainingCourse $trainingCourse): Response
    {
        $this->authorize('view', $trainingCourse);

        $user = $request->user();
        $trainingCourse->load(['category:id,name', 'track:id,name,slug']);

        $selected = $trainingCourse->versions()
            ->addSelect(['open_assignments_count' => TrainingAssignment::query()
                ->selectRaw('count(*)')
                ->whereColumn('course_version_id', 'ro_training_course_versions.id')
                ->where('status', '!=', TrainingAssignmentStatus::Completed->value)])
            ->orderByRaw('id = ? desc', [(int) $trainingCourse->current_version_id])
            ->orderByRaw('status = ? desc', [TrainingContentStatus::Draft->value])
            ->orderByDesc('version_number')
            ->first();

        $draft = $trainingCourse->versions()
            ->where('status', TrainingContentStatus::Draft->value)
            ->withCount('lessons')
            ->first();
        $draft?->setRelation('course', $trainingCourse);

        $selected?->setRelation('course', $trainingCourse);
        $selected?->load(['lessons.media', 'lessons.contents:id,lesson_id,locale', 'assessmentVersions.assessment']);

        return Inertia::render('RecruiterOperations/training/manage/courses/show', [
            'course' => [
                'id' => $trainingCourse->id,
                'title' => $trainingCourse->title,
                'description' => $trainingCourse->description,
                'category' => $trainingCourse->category->name ?? null,
                'track' => $trainingCourse->track === null ? null : ['name' => $trainingCourse->track->name, 'slug' => $trainingCourse->track->slug],
                'status' => $trainingCourse->status->value,
                'status_label' => $trainingCourse->status->label(),
            ],
            'selectedVersion' => $selected !== null ? [
                'id' => $selected->id,
                'description' => $selected->description,
                'estimated_minutes' => $selected->estimated_minutes,
                'duration_minutes' => $selected->estimatedMinutes(),
                'open_assignments_count' => (int) $selected->getAttribute('open_assignments_count'),
                'lessons' => $selected->lessons->map(fn (TrainingLesson $lesson) => [
                    'id' => $lesson->id,
                    'module' => $lesson->module,
                    'title' => $lesson->title,
                    'content_type' => $lesson->content_type->value,
                    'content_type_label' => $lesson->content_type->label(),
                    'duration_minutes' => $lesson->estimatedMinutes(),
                    'is_required' => $lesson->is_required,
                    'has_file' => $lesson->file() !== null,
                    'has_body' => filled($lesson->body),
                    'translations' => $lesson->contents
                        ->filter(fn (TrainingLessonContent $content) => ! $content->locale->isCanonical())
                        ->map(fn (TrainingLessonContent $content) => $content->locale->nativeLabel())
                        ->values()
                        ->all(),
                ])->values()->all(),
                'quizzes' => $selected->assessmentVersions->map(fn (AssessmentVersion $quiz) => [
                    'id' => $quiz->id,
                    'assessment_id' => $quiz->assessment_id,
                    'title' => $quiz->assessment->title,
                    'label' => $quiz->label(),
                    'status_label' => $quiz->status->label(),
                ])->values()->all(),
                'can' => [
                    'update' => $user->can('update', $selected),
                ],
            ] : null,
            'draft' => $draft !== null ? [
                'id' => $draft->id,
                'label' => "Version {$draft->version_number}",
                'lessons_count' => (int) $draft->lessons_count,
                'is_shown' => $draft->id === $selected?->id,
                'can_publish' => $user->can('publish', $draft),
            ] : null,
            'can' => [
                'update' => $user->can('update', $trainingCourse),
                'archive' => $user->can('archive', $trainingCourse),
                'restore' => $user->can('restore', $trainingCourse),
                'assign' => $user->can('assign', TrainingCourse::class)
                    && $trainingCourse->isAssignable()
                    && $selected?->id === $trainingCourse->current_version_id
                    && $selected->lessons->isNotEmpty(),
            ],
            'quizOptions' => $selected !== null && $selected->isEditable() ? $this->quizOptions() : [],
        ]);
    }

    /**
     * Published training quizzes that can be linked, as their current version.
     *
     * @return list<array{value: string, label: string}>
     */
    protected function quizOptions(): array
    {
        return Assessment::query()
            ->assignable()
            ->with('currentVersion:id,version_number')
            ->orderBy('title')
            ->get()
            ->map(fn (Assessment $assessment) => [
                'value' => (string) $assessment->current_version_id,
                'label' => $assessment->title.' · '.($assessment->currentVersion?->label() ?? ''),
            ])
            ->values()
            ->all();
    }

    public function edit(Request $request, TrainingCourse $trainingCourse): Response
    {
        $this->authorize('update', $trainingCourse);

        return Inertia::render('RecruiterOperations/training/manage/courses/edit', [
            'course' => [
                'id' => $trainingCourse->id,
                'category_id' => $trainingCourse->category_id,
                'training_track_id' => $trainingCourse->training_track_id,
                'title' => $trainingCourse->title,
                'description' => $trainingCourse->description,
            ],
            'categories' => $this->categoryOptions(true, $trainingCourse->category_id),
            'tracks' => $this->tracks->formOptions(),
            'trackLocked' => $this->tracks->isTrackLocked($trainingCourse),
        ]);
    }

    public function update(TrainingCourseRequest $request, TrainingCourse $trainingCourse): RedirectResponse
    {
        $this->content->updateCourse($trainingCourse, $request->validated(), $request->user());

        return to_route('recruiter.training.manage.courses.show', $trainingCourse)->with('success', 'Course updated.');
    }

    public function archive(Request $request, TrainingCourse $trainingCourse): RedirectResponse
    {
        $this->content->archiveCourse($trainingCourse, $request->user());

        return back()->with('success', 'Course archived. Recruiters already assigned can still finish it.');
    }

    public function restore(Request $request, TrainingCourse $trainingCourse): RedirectResponse
    {
        $this->content->restoreCourse($trainingCourse, $request->user());

        return back()->with('success', 'Course restored.');
    }

    public function storeVersion(Request $request, TrainingCourse $trainingCourse): RedirectResponse
    {
        $validated = $request->validate([
            'source_version_id' => [
                'nullable',
                'integer',
                Rule::exists('ro_training_course_versions', 'id')->where('course_id', $trainingCourse->id),
            ],
        ]);

        $source = isset($validated['source_version_id'])
            ? TrainingCourseVersion::query()->find((int) $validated['source_version_id'])
            : null;

        $version = $this->content->createVersion($trainingCourse, $request->user(), $source);

        return to_route('recruiter.training.manage.courses.show', [$trainingCourse, 'version' => $version->id])
            ->with('success', "Draft {$version->label()} created. Edit it, then publish.");
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    protected function categoryOptions(bool $activeOnly, ?int $alwaysInclude = null): array
    {
        return TrainingCategory::query()
            ->when($activeOnly, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('is_active', true)
                ->when($alwaysInclude, fn (Builder $or, int $id) => $or->orWhere('id', $id))))
            ->ordered()
            ->get(['id', 'name', 'is_active'])
            ->map(fn (TrainingCategory $category) => [
                'id' => $category->id,
                'label' => $category->name.($category->is_active ? '' : ' (inactive)'),
            ])
            ->values()
            ->all();
    }
}
