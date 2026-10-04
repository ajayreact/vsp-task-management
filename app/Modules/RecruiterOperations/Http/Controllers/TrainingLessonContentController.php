<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Enums\TrainingSectionKind;
use App\Modules\RecruiterOperations\Http\Requests\TrainingLessonContentRequest;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingLessonContentService;
use App\Modules\RecruiterOperations\Services\TrainingPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The structured lesson editor: English sections, their translations and
 * review states. Read-only for older versions kept as history; saving goes
 * through TrainingLessonContentService, which enforces the same rule again.
 */
class TrainingLessonContentController extends Controller
{
    public function __construct(
        protected TrainingLessonContentService $contents,
        protected TrainingPresenter $presenter,
    ) {}

    public function edit(Request $request, TrainingLesson $trainingLesson): Response
    {
        $this->authorize('view', $trainingLesson);

        $trainingLesson->load(['version.course', 'contents']);
        $language = TrainingLanguage::tryFrom($request->string('language')->value()) ?? TrainingLanguage::default();

        return Inertia::render('RecruiterOperations/training/manage/lessons/content', [
            'lesson' => [
                'id' => $trainingLesson->id,
                'title' => $trainingLesson->title,
                'languages' => $this->presenter->languages($trainingLesson),
            ],
            'version' => [
                'id' => $trainingLesson->version->id,
                'label' => $trainingLesson->version->label(),
                'status' => $trainingLesson->version->status->value,
                'course' => ['id' => $trainingLesson->version->course->id, 'title' => $trainingLesson->version->course->title],
            ],
            'language' => $language->value,
            'sectionKinds' => TrainingSectionKind::options(),
            'can' => ['update' => $request->user()->can('update', $trainingLesson)],
        ]);
    }

    public function update(TrainingLessonContentRequest $request, TrainingLesson $trainingLesson, string $language): RedirectResponse
    {
        $locale = TrainingLanguage::tryFrom($language);
        abort_if($locale === null, 404);

        /** @var list<array{kind?: mixed, heading?: mixed, body?: mixed}> $sections */
        $sections = $request->validated('sections');

        $this->contents->save($trainingLesson, $locale, $sections, $request->user());

        return to_route('recruiter.training.manage.lessons.content.edit', [$trainingLesson, 'language' => $locale->value])
            ->with('success', $locale->label().' content saved. Recruiters see the change now.');
    }

    public function destroy(Request $request, TrainingLesson $trainingLesson, string $language): RedirectResponse
    {
        $locale = TrainingLanguage::tryFrom($language);
        abort_if($locale === null, 404);

        $this->contents->remove($trainingLesson, $locale, $request->user());

        return to_route('recruiter.training.manage.lessons.content.edit', [$trainingLesson, 'language' => $locale->value])
            ->with('success', $locale->label().' translation removed.');
    }
}
