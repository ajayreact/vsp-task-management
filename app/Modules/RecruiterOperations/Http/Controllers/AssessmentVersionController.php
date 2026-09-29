<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Http\Requests\AssessmentVersionRequest;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentContentService;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentPresenter;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentQuestionService;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Version settings and lifecycle (publish, archive, discard), the manager
 * preview, and adding Question Bank items to a draft.
 */
class AssessmentVersionController extends Controller
{
    public function __construct(
        protected AssessmentContentService $content,
        protected AssessmentQuestionService $questions,
        protected AssessmentPresenter $presenter,
    ) {}

    public function update(AssessmentVersionRequest $request, AssessmentVersion $assessmentVersion): RedirectResponse
    {
        /** @var array{instructions?: string|null, passing_percentage?: int|null, time_limit_minutes?: int|null, max_attempts?: int|null, randomize_questions?: bool|null, randomize_options?: bool|null, show_result?: bool|null, allow_review?: bool|null} $data */
        $data = $request->validated();
        $this->content->updateVersion($assessmentVersion, $data, $request->user());

        return back()->with('success', 'Settings saved.');
    }

    public function publish(Request $request, AssessmentVersion $assessmentVersion): RedirectResponse
    {
        $this->content->publishVersion($assessmentVersion, $request->user());

        return to_route('recruiter.assessments.manage.show', [$assessmentVersion->assessment_id, 'version' => $assessmentVersion->id])
            ->with('success', "{$assessmentVersion->label()} published. New assignments will use it.");
    }

    public function archive(Request $request, AssessmentVersion $assessmentVersion): RedirectResponse
    {
        $this->content->archiveVersion($assessmentVersion, $request->user());

        return back()->with('success', "{$assessmentVersion->label()} archived. Recruiters already on it keep it.");
    }

    public function destroy(Request $request, AssessmentVersion $assessmentVersion): RedirectResponse
    {
        $assessmentId = $assessmentVersion->assessment_id;
        $this->content->discardDraft($assessmentVersion, $request->user());

        return to_route('recruiter.assessments.manage.show', $assessmentId)->with('success', 'Draft discarded.');
    }

    /**
     * What recruiters will see, with the answer key for the manager.
     */
    public function preview(AssessmentVersion $assessmentVersion): Response
    {
        $assessmentVersion->load('assessment');

        return Inertia::render('RecruiterOperations/assessments/manage/preview', [
            'assessment' => $this->presenter->assessmentSummary($assessmentVersion->assessment),
            'version' => $this->presenter->settings($assessmentVersion),
            'questions' => $assessmentVersion->questions()->with('options')->get()
                ->map(fn (AssessmentQuestion $question) => $this->presenter->managerQuestion($question))
                ->values()
                ->all(),
        ]);
    }

    public function bank(Request $request, AssessmentVersion $assessmentVersion): Response
    {
        $assessmentVersion->load('assessment');
        $type = QuestionType::tryFrom($request->string('type')->value());
        $category = $request->string('category')->trim()->limit(100, '')->value();
        $search = $request->string('search')->trim()->limit(100, '')->value();
        $inVersion = $assessmentVersion->questions()->pluck('bank_question_id')->filter()->map(fn ($id) => (int) $id)->all();

        $rows = AssessmentQuestion::query()
            ->bank()
            ->with('options')
            ->when($type, fn (Builder $query, QuestionType $value) => $query->where('type', $value->value))
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->when($search !== '', fn (Builder $query) => $query->where('prompt', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 25))
            ->withQueryString()
            ->through(fn (AssessmentQuestion $question) => [
                ...$this->presenter->managerQuestion($question),
                'in_version' => in_array($question->id, $inVersion, true),
            ]);

        return Inertia::render('RecruiterOperations/assessments/manage/bank-picker', [
            'assessment' => $this->presenter->assessmentSummary($assessmentVersion->assessment),
            'version' => $this->presenter->settings($assessmentVersion),
            'questions' => $rows,
            'filters' => ['type' => $type->value ?? '', 'category' => $category, 'search' => $search],
            'types' => QuestionType::options(),
            'categories' => $this->questions->categories(),
        ]);
    }

    public function addFromBank(Request $request, AssessmentVersion $assessmentVersion): RedirectResponse
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1', 'max:200'],
            'question_ids.*' => ['integer', 'distinct'],
        ]);

        $result = $this->questions->addFromBank($assessmentVersion, array_map('intval', $validated['question_ids']), $request->user());
        $message = $result['added'] === 1 ? '1 question added.' : "{$result['added']} questions added.";

        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} already in this version were skipped.";
        }

        return to_route('recruiter.assessments.manage.show', [$assessmentVersion->assessment_id, 'version' => $assessmentVersion->id])
            ->with('success', $message);
    }
}
