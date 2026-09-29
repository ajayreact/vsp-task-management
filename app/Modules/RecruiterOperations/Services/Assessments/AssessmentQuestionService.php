<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\QuestionSource;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Models\AssessmentOption;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Questions in the Question Bank and in draft assessment versions.
 *
 * - bank questions (no version) can be edited any time; quizzes hold copies;
 * - adding a bank question to a version copies it, so later bank edits never
 *   change a quiz;
 * - questions of published or archived versions never change;
 * - removing a bank question archives it (soft delete); removing a draft
 *   version's question deletes the copy.
 *
 * @phpstan-type QuestionData array{type: string, prompt: string, points?: int|null, explanation?: string|null, category?: string|null, is_required?: bool|null, options?: list<array{text?: string|null, is_correct?: bool|null}>|null}
 */
class AssessmentQuestionService
{
    /**
     * @param  QuestionData  $data
     */
    public function createBankQuestion(array $data, User $actor): AssessmentQuestion
    {
        $this->ensureManager($actor);

        return DB::transaction(fn () => $this->persist(new AssessmentQuestion, null, $data, $actor));
    }

    /**
     * @param  QuestionData  $data
     */
    public function createVersionQuestion(AssessmentVersion $version, array $data, User $actor): AssessmentQuestion
    {
        $this->ensureManager($actor);
        $this->ensureDraft($version);

        return DB::transaction(fn () => $this->persist(new AssessmentQuestion, $version, $data, $actor));
    }

    /**
     * @param  QuestionData  $data
     */
    public function updateQuestion(AssessmentQuestion $question, array $data, User $actor): AssessmentQuestion
    {
        $this->ensureManager($actor);
        $this->ensureEditable($question);

        return DB::transaction(fn () => $this->persist($question, $question->version, $data, $actor));
    }

    public function deleteQuestion(AssessmentQuestion $question, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureEditable($question);

        DB::transaction(function () use ($question, $actor) {
            if ($question->isInBank()) {
                $question->forceFill(['updated_by_user_id' => $actor->id])->save();
                $question->delete();

                return;
            }

            $version = $question->version;
            $question->forceDelete();

            if ($version !== null) {
                $this->resequence($version);
            }
        });
    }

    public function restoreBankQuestion(AssessmentQuestion $question, User $actor): AssessmentQuestion
    {
        $this->ensureManager($actor);

        if (! $question->isInBank() || ! $question->trashed()) {
            throw ValidationException::withMessages(['question' => 'Only an archived Question Bank item can be restored.']);
        }

        $question->restore();
        $question->forceFill(['updated_by_user_id' => $actor->id])->save();

        return $question;
    }

    /**
     * A new bank question copied from an existing one, marked as a copy so it
     * is not taken for a duplicate.
     */
    public function duplicateBankQuestion(AssessmentQuestion $question, User $actor): AssessmentQuestion
    {
        $this->ensureManager($actor);

        if (! $question->isInBank() || $question->trashed()) {
            throw ValidationException::withMessages(['question' => 'Only an active Question Bank item can be duplicated.']);
        }

        return DB::transaction(function () use ($question, $actor) {
            $copy = $this->copy($question, null, $actor);
            $copy->forceFill(['prompt' => 'Copy of '.$question->prompt, 'source' => QuestionSource::Manual, 'import_batch' => null, 'import_row' => null])->save();

            return $copy;
        });
    }

    /**
     * Copies bank questions into a draft version. A question already in the
     * version (same bank item or same wording) is skipped.
     *
     * @param  list<int>  $questionIds
     * @return array{added: int, skipped: int}
     */
    public function addFromBank(AssessmentVersion $version, array $questionIds, User $actor): array
    {
        $this->ensureManager($actor);
        $this->ensureDraft($version);

        $questionIds = array_values(array_unique(array_map('intval', $questionIds)));

        if ($questionIds === []) {
            throw ValidationException::withMessages(['question_ids' => 'Choose at least one question.']);
        }

        $bank = AssessmentQuestion::query()->bank()->with('options')->whereKey($questionIds)->get()->keyBy('id');

        if ($bank->count() !== count($questionIds)) {
            throw ValidationException::withMessages(['question_ids' => 'Choose questions from the active Question Bank.']);
        }

        return DB::transaction(function () use ($version, $questionIds, $bank, $actor) {
            AssessmentVersion::query()->whereKey($version->id)->lockForUpdate()->first();

            $present = $version->questions()->get(['id', 'bank_question_id', 'prompt_hash']);
            $bankIds = $present->pluck('bank_question_id')->filter()->map(fn ($id) => (int) $id)->all();
            $hashes = $present->pluck('prompt_hash')->all();
            $added = 0;
            $skipped = 0;

            foreach ($questionIds as $id) {
                /** @var AssessmentQuestion $source */
                $source = $bank->get($id);

                if (in_array($source->id, $bankIds, true) || in_array($source->prompt_hash, $hashes, true)) {
                    $skipped++;

                    continue;
                }

                $this->copy($source, $version, $actor);
                $hashes[] = $source->prompt_hash;
                $added++;
            }

            return ['added' => $added, 'skipped' => $skipped];
        });
    }

    public function moveQuestion(AssessmentQuestion $question, string $direction, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureEditable($question);

        $version = $question->version;

        if ($version === null) {
            return;
        }

        DB::transaction(function () use ($question, $version, $direction) {
            $this->resequence($version);

            $ordered = $version->questions()->get()->values();
            $index = $ordered->search(fn (AssessmentQuestion $item) => $item->id === $question->id);

            if (! is_int($index)) {
                return;
            }

            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($target < 0 || $target >= $ordered->count()) {
                return;
            }

            $current = $ordered[$index];
            $neighbour = $ordered[$target];
            [$currentOrder, $neighbourOrder] = [$current->sort_order, $neighbour->sort_order];

            $current->forceFill(['sort_order' => $neighbourOrder])->saveQuietly();
            $neighbour->forceFill(['sort_order' => $currentOrder])->saveQuietly();
        });
    }

    /**
     * Copies a question and its options into a version (or into the bank
     * when $version is null).
     */
    public function copy(AssessmentQuestion $source, ?AssessmentVersion $version, User $actor): AssessmentQuestion
    {
        $source->loadMissing('options');

        $copy = $source->replicate(['assessment_version_id', 'bank_question_id', 'sort_order', 'created_by_user_id', 'updated_by_user_id', 'deleted_at']);
        $copy->forceFill([
            'assessment_version_id' => $version?->id,
            'bank_question_id' => $version === null ? null : ($source->isInBank() ? $source->id : $source->bank_question_id),
            'sort_order' => $version === null ? 0 : ((int) $version->questions()->max('sort_order')) + 1,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ])->save();

        foreach ($source->options as $option) {
            $this->storeOption($copy, $option->option_key, $option->text, $option->is_correct, $option->sort_order);
        }

        return $copy;
    }

    /**
     * Validates options against the question type and returns them cleaned.
     *
     * @param  list<array{text?: string|null, is_correct?: bool|null}>|null  $options
     * @return list<array{text: string, correct: bool}>
     */
    public function cleanOptions(QuestionType $type, ?array $options): array
    {
        $clean = [];

        foreach ($options ?? [] as $option) {
            $text = trim((string) ($option['text'] ?? ''));

            if ($text !== '') {
                $clean[] = ['text' => $text, 'correct' => (bool) ($option['is_correct'] ?? false)];
            }
        }

        $problems = QuestionRules::problems($type, $clean);

        if ($problems !== []) {
            throw ValidationException::withMessages(['options' => $problems]);
        }

        return $type->usesOptions() ? $clean : [];
    }

    /**
     * Stores a validated question. Also used by the Excel import.
     *
     * @param  list<array{text: string, correct: bool}>  $options
     * @param  array{source?: QuestionSource, import_batch?: string|null, import_row?: int|null}  $origin
     */
    public function store(
        ?AssessmentVersion $version,
        QuestionType $type,
        string $prompt,
        int $points,
        ?string $explanation,
        ?string $category,
        array $options,
        User $actor,
        array $origin = [],
    ): AssessmentQuestion {
        $question = new AssessmentQuestion;
        $question->fill([
            'type' => $type,
            'prompt' => trim($prompt),
            'points' => $points,
            'explanation' => blank($explanation) ? null : trim((string) $explanation),
            'category' => $this->matchCategory(QuestionRules::normalizeCategory($category)),
            'is_required' => true,
        ]);
        $question->forceFill([
            'assessment_version_id' => $version?->id,
            'sort_order' => $version === null ? 0 : ((int) $version->questions()->max('sort_order')) + 1,
            'source' => $origin['source'] ?? QuestionSource::Manual,
            'import_batch' => $origin['import_batch'] ?? null,
            'import_row' => $origin['import_row'] ?? null,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ])->save();

        foreach ($options as $index => $option) {
            $this->storeOption($question, QuestionRules::optionKey($index), $option['text'], $option['correct'], $index + 1);
        }

        return $question;
    }

    /**
     * Existing categories, spelled as first saved.
     *
     * @return list<string>
     */
    public function categories(): array
    {
        return AssessmentQuestion::query()
            ->bank()
            ->whereNotNull('category')
            ->orderBy('category')
            ->distinct()
            ->pluck('category')
            ->map(fn ($category) => (string) $category)
            ->unique(fn (string $category) => mb_strtolower($category))
            ->values()
            ->all();
    }

    /**
     * Reuses the existing spelling of a category that differs only by case.
     */
    public function matchCategory(?string $category): ?string
    {
        if ($category === null) {
            return null;
        }

        foreach ($this->categories() as $existing) {
            if (mb_strtolower($existing) === mb_strtolower($category)) {
                return $existing;
            }
        }

        return $category;
    }

    public function ensureManager(User $actor): void
    {
        if (! $actor->can(Ability::RecruiterAccess->value) || ! $actor->can(Ability::ManageRecruiterAssessments->value)) {
            throw new AuthorizationException('You cannot manage recruiter assessments.');
        }
    }

    // Internals

    /**
     * @param  QuestionData  $data
     */
    protected function persist(AssessmentQuestion $question, ?AssessmentVersion $version, array $data, User $actor): AssessmentQuestion
    {
        $type = QuestionType::tryFrom($data['type']);

        if ($type === null) {
            throw ValidationException::withMessages(['type' => 'Choose a question type.']);
        }

        $options = $this->cleanOptions($type, $data['options'] ?? null);
        $points = (int) ($data['points'] ?? 1);

        if ($points < 1 || $points > QuestionRules::MAX_POINTS) {
            throw ValidationException::withMessages(['points' => 'Points must be between 1 and '.QuestionRules::MAX_POINTS.'.']);
        }

        if (! $question->exists) {
            return $this->store($version, $type, $data['prompt'], $points, $data['explanation'] ?? null, $data['category'] ?? null, $options, $actor);
        }

        $question->fill([
            'type' => $type,
            'prompt' => trim($data['prompt']),
            'points' => $points,
            'explanation' => blank($data['explanation'] ?? null) ? null : trim((string) $data['explanation']),
            'category' => $this->matchCategory(QuestionRules::normalizeCategory($data['category'] ?? null)),
            'is_required' => (bool) ($data['is_required'] ?? true),
        ]);
        $question->updated_by_user_id = $actor->id;
        $question->save();

        $question->options()->delete();

        foreach ($options as $index => $option) {
            $this->storeOption($question, QuestionRules::optionKey($index), $option['text'], $option['correct'], $index + 1);
        }

        $question->unsetRelation('options');

        return $question;
    }

    protected function storeOption(AssessmentQuestion $question, string $key, string $text, bool $correct, int $sortOrder): AssessmentOption
    {
        $option = new AssessmentOption;
        $option->forceFill([
            'question_id' => $question->id,
            'option_key' => $key,
            'text' => $text,
            'is_correct' => $correct,
            'sort_order' => $sortOrder,
        ])->save();

        return $option;
    }

    protected function ensureEditable(AssessmentQuestion $question): void
    {
        if ($question->trashed()) {
            throw ValidationException::withMessages(['question' => 'Restore this question before changing it.']);
        }

        if (! $question->isInBank()) {
            $version = $question->version;

            if ($version === null || ! $version->isDraft()) {
                throw ValidationException::withMessages([
                    'question' => 'Questions in published or archived versions cannot be changed. Create a new version instead.',
                ]);
            }
        }
    }

    protected function ensureDraft(AssessmentVersion $version): void
    {
        if (! $version->isDraft()) {
            throw ValidationException::withMessages([
                'version' => 'Published and archived versions cannot be changed. Create a new version instead.',
            ]);
        }
    }

    protected function resequence(AssessmentVersion $version): void
    {
        $position = 1;

        foreach ($version->questions()->get() as $question) {
            if ($question->sort_order !== $position) {
                $question->forceFill(['sort_order' => $position])->saveQuietly();
            }

            $position++;
        }
    }
}
