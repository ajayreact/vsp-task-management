<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\TrainingLessonContentType;
use App\Modules\RecruiterOperations\Http\Requests\TrainingLessonRequest;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use App\Modules\RecruiterOperations\Services\TrainingPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lesson authoring inside a draft version, plus a read-only preview (with
 * Listen to Lesson) for any version.
 */
class TrainingLessonController extends Controller
{
    public function __construct(
        protected TrainingContentService $content,
        protected TrainingPresenter $presenter,
    ) {}

    public function create(Request $request, TrainingCourseVersion $trainingVersion): Response
    {
        $this->authorize('update', $trainingVersion);

        return Inertia::render('RecruiterOperations/training/manage/lessons/create', [
            ...$this->formOptions(),
            'version' => $this->versionSummary($trainingVersion),
        ]);
    }

    public function store(TrainingLessonRequest $request, TrainingCourseVersion $trainingVersion): RedirectResponse
    {
        $this->content->createLesson(
            $trainingVersion,
            Arr::except($request->validated(), ['file']),
            $request->file('file'),
            $request->user(),
        );

        return to_route('recruiter.training.manage.courses.show', [$trainingVersion->course_id, 'version' => $trainingVersion->id])
            ->with('success', 'Lesson added.');
    }

    public function show(Request $request, TrainingLesson $trainingLesson): Response
    {
        $this->authorize('view', $trainingLesson);

        $trainingLesson->load('version.course');

        return Inertia::render('RecruiterOperations/training/manage/lessons/show', [
            'lesson' => $this->presenter->lesson($trainingLesson),
            'version' => $this->versionSummary($trainingLesson->version),
            'audio' => $this->presenter->audio($trainingLesson),
            'can' => [
                'update' => $request->user()->can('update', $trainingLesson),
                'delete' => $request->user()->can('delete', $trainingLesson),
            ],
        ]);
    }

    public function edit(Request $request, TrainingLesson $trainingLesson): Response
    {
        $this->authorize('update', $trainingLesson);

        return Inertia::render('RecruiterOperations/training/manage/lessons/edit', [
            ...$this->formOptions(),
            'version' => $this->versionSummary($trainingLesson->version),
            'lesson' => $this->presenter->lesson($trainingLesson),
        ]);
    }

    public function update(TrainingLessonRequest $request, TrainingLesson $trainingLesson): RedirectResponse
    {
        $this->content->updateLesson(
            $trainingLesson,
            Arr::except($request->validated(), ['file']),
            $request->file('file'),
            $request->user(),
        );

        return to_route('recruiter.training.manage.courses.show', [$trainingLesson->version->course_id, 'version' => $trainingLesson->course_version_id])
            ->with('success', 'Lesson updated.');
    }

    public function destroy(Request $request, TrainingLesson $trainingLesson): RedirectResponse
    {
        $version = $trainingLesson->version;
        $this->content->deleteLesson($trainingLesson, $request->user());

        return to_route('recruiter.training.manage.courses.show', [$version->course_id, 'version' => $version->id])
            ->with('success', 'Lesson deleted.');
    }

    public function move(Request $request, TrainingLesson $trainingLesson): RedirectResponse
    {
        $validated = $request->validate(['direction' => ['required', 'in:up,down']]);

        $this->content->moveLesson($trainingLesson, $validated['direction'], $request->user());

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'contentTypes' => array_map(fn (TrainingLessonContentType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'uses_file' => $type->usesFile(),
                'accept' => implode(',', array_map(fn (string $extension) => '.'.$extension, $type->allowedExtensions())),
            ], TrainingLessonContentType::cases()),
            'maxUploadKilobytes' => (int) config('recruiter-training.media.max_kilobytes', 614400),
        ];
    }

    /**
     * @return array{id: int, label: string, status: string, course: array{id: int, title: string}, modules: list<string>}
     */
    protected function versionSummary(TrainingCourseVersion $version): array
    {
        return [
            'id' => $version->id,
            'label' => $version->label(),
            'status' => $version->status->value,
            'course' => ['id' => $version->course->id, 'title' => $version->course->title],
            'modules' => $version->lessons()->whereNotNull('module')->pluck('module')->unique()->values()->all(),
        ];
    }
}
