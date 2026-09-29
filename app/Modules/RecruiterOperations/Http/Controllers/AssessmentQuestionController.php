<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RecruiterOperations\Enums\QuestionSource;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Http\Requests\AssessmentQuestionRequest;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentPresenter;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentQuestionService;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Question Bank and the questions of draft versions
 * (recruiter.assessments.manage).
 */
class AssessmentQuestionController extends Controller
{
    public function __construct(
        protected AssessmentQuestionService $questions,
        protected AssessmentPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $type = QuestionType::tryFrom($request->string('type')->value());
        $source = QuestionSource::tryFrom($request->string('source')->value());
        $category = $request->string('category')->trim()->limit(100, '')->value();
        $search = $request->string('search')->trim()->limit(100, '')->value();
        $archived = $request->boolean('archived');
        $batch = $request->string('batch')->trim()->value();

        $rows = AssessmentQuestion::query()
            ->bank()
            ->with(['options', 'creator:id,name'])
            ->when($archived, fn (Builder $query) => $query->onlyTrashed())
            ->when($type, fn (Builder $query, QuestionType $value) => $query->where('type', $value->value))
            ->when($source, fn (Builder $query, QuestionSource $value) => $query->where('source', $value->value))
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->when(preg_match('/^[0-9a-f-]{36}$/i', $batch) === 1, fn (Builder $query) => $query->where('import_batch', $batch))
            ->when($search !== '', fn (Builder $query) => $query->where('prompt', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 25))
            ->withQueryString()
            ->through(fn (AssessmentQuestion $question) => [
                ...$this->presenter->managerQuestion($question),
                'created_by' => $question->creator?->name,
                'created_at' => $question->created_at->toIso8601String(),
            ]);

        return Inertia::render('RecruiterOperations/assessments/bank/index', [
            'questions' => $rows,
            'filters' => [
                'type' => $type->value ?? '',
                'source' => $source->value ?? '',
                'category' => $category,
                'search' => $search,
                'archived' => $archived,
                'batch' => preg_match('/^[0-9a-f-]{36}$/i', $batch) === 1 ? $batch : '',
            ],
            'types' => QuestionType::options(),
            'sources' => QuestionSource::options(),
            'categories' => $this->questions->categories(),
        ]);
    }

    public function create(): Response
    {
        return $this->form(null, null);
    }

    public function store(AssessmentQuestionRequest $request): RedirectResponse
    {
        $question = $this->questions->createBankQuestion($this->data($request), $request->user());

        return $request->boolean('add_another')
            ? to_route('recruiter.assessments.questions.create')->with('success', 'Question saved to the Question Bank.')
            : to_route('recruiter.assessments.questions.show', $question)->with('success', 'Question saved to the Question Bank.');
    }

    public function show(Request $request, AssessmentQuestion $assessmentQuestion): Response
    {
        $user = $request->user();
        $assessmentQuestion->load(['options', 'creator:id,name', 'version.assessment']);

        return Inertia::render('RecruiterOperations/assessments/bank/show', [
            'question' => [
                ...$this->presenter->managerQuestion($assessmentQuestion),
                'created_by' => $assessmentQuestion->creator?->name,
                'created_at' => $assessmentQuestion->created_at->toIso8601String(),
                'in_bank' => $assessmentQuestion->isInBank(),
                'version' => $assessmentQuestion->version !== null ? [
                    'id' => $assessmentQuestion->version->id,
                    'label' => $assessmentQuestion->version->label(),
                    'assessment_id' => $assessmentQuestion->version->assessment_id,
                    'assessment_title' => $assessmentQuestion->version->assessment->title,
                ] : null,
                'used_in' => AssessmentQuestion::query()->where('bank_question_id', $assessmentQuestion->id)->count(),
            ],
            'can' => [
                'update' => $user->can('update', $assessmentQuestion),
                'delete' => $user->can('delete', $assessmentQuestion),
                'duplicate' => $user->can('duplicate', $assessmentQuestion),
                'restore' => $user->can('restore', $assessmentQuestion),
            ],
        ]);
    }

    public function edit(AssessmentQuestion $assessmentQuestion): Response
    {
        $assessmentQuestion->load(['options', 'version']);

        return $this->form($assessmentQuestion, $assessmentQuestion->version);
    }

    public function update(AssessmentQuestionRequest $request, AssessmentQuestion $assessmentQuestion): RedirectResponse
    {
        $this->questions->updateQuestion($assessmentQuestion, $this->data($request), $request->user());

        return $this->afterChange($assessmentQuestion, 'Question updated.');
    }

    public function destroy(Request $request, AssessmentQuestion $assessmentQuestion): RedirectResponse
    {
        $inBank = $assessmentQuestion->isInBank();
        $this->questions->deleteQuestion($assessmentQuestion, $request->user());

        return $inBank
            ? to_route('recruiter.assessments.questions.index')->with('success', 'Question archived. Quizzes that already use it keep their copy.')
            : $this->afterChange($assessmentQuestion, 'Question removed from this draft.');
    }

    public function duplicate(Request $request, AssessmentQuestion $assessmentQuestion): RedirectResponse
    {
        $copy = $this->questions->duplicateBankQuestion($assessmentQuestion, $request->user());

        return to_route('recruiter.assessments.questions.edit', $copy)->with('success', 'Copy created. Edit it before saving.');
    }

    public function restore(Request $request, AssessmentQuestion $assessmentQuestion): RedirectResponse
    {
        $this->questions->restoreBankQuestion($assessmentQuestion, $request->user());

        return to_route('recruiter.assessments.questions.show', $assessmentQuestion)->with('success', 'Question restored.');
    }

    public function createForVersion(AssessmentVersion $assessmentVersion): Response
    {
        return $this->form(null, $assessmentVersion);
    }

    public function storeForVersion(AssessmentQuestionRequest $request, AssessmentVersion $assessmentVersion): RedirectResponse
    {
        $this->questions->createVersionQuestion($assessmentVersion, $this->data($request), $request->user());

        return $request->boolean('add_another')
            ? to_route('recruiter.assessments.manage.questions.create', $assessmentVersion)->with('success', 'Question added.')
            : to_route('recruiter.assessments.manage.show', [$assessmentVersion->assessment_id, 'version' => $assessmentVersion->id])->with('success', 'Question added.');
    }

    public function move(Request $request, AssessmentQuestion $assessmentQuestion): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $this->questions->moveQuestion($assessmentQuestion, $direction, $request->user());

        return back();
    }

    protected function form(?AssessmentQuestion $question, ?AssessmentVersion $version): Response
    {
        $version?->loadMissing('assessment');

        return Inertia::render('RecruiterOperations/assessments/bank/form', [
            'question' => $question !== null ? $this->presenter->managerQuestion($question) : null,
            'context' => $version !== null ? [
                'version_id' => $version->id,
                'version_label' => $version->label(),
                'assessment_id' => $version->assessment_id,
                'assessment_title' => $version->assessment->title,
            ] : null,
            'types' => QuestionType::options(),
            'categories' => $this->questions->categories(),
        ]);
    }

    /**
     * @return array{type: string, prompt: string, points?: int|null, explanation?: string|null, category?: string|null, is_required?: bool|null, options?: list<array{text?: string|null, is_correct?: bool|null}>|null}
     */
    protected function data(AssessmentQuestionRequest $request): array
    {
        $validated = $request->validated();

        return [
            'type' => (string) $validated['type'],
            'prompt' => (string) $validated['prompt'],
            'points' => isset($validated['points']) ? (int) $validated['points'] : null,
            'explanation' => $validated['explanation'] ?? null,
            'category' => $validated['category'] ?? null,
            'is_required' => isset($validated['is_required']) ? (bool) $validated['is_required'] : null,
            'options' => array_values(array_map(fn ($option) => [
                'text' => is_array($option) ? ($option['text'] ?? null) : null,
                'is_correct' => is_array($option) ? (bool) ($option['is_correct'] ?? false) : false,
            ], $validated['options'] ?? [])),
        ];
    }

    protected function afterChange(AssessmentQuestion $question, string $message): RedirectResponse
    {
        if ($question->isInBank()) {
            return to_route('recruiter.assessments.questions.show', $question)->with('success', $message);
        }

        $version = AssessmentVersion::query()->find($question->assessment_version_id);

        return $version !== null
            ? to_route('recruiter.assessments.manage.show', [$version->assessment_id, 'version' => $version->id])->with('success', $message)
            : to_route('recruiter.assessments.questions.index')->with('success', $message);
    }
}
