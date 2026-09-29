<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Enums\QuestionSource;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Models\AssessmentOption;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds questions for test setup: a Question Bank item unless forVersion()
 * is used. The type states replace the options with a known answer key.
 *
 * @extends Factory<AssessmentQuestion>
 */
class AssessmentQuestionFactory extends Factory
{
    /** @var class-string<AssessmentQuestion> */
    protected $model = AssessmentQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_version_id' => null,
            'bank_question_id' => null,
            'type' => QuestionType::ShortAnswer,
            'prompt' => 'Question '.fake()->unique()->numberBetween(1, 999999).': explain STEM OPT in one sentence.',
            'points' => 1,
            'explanation' => null,
            'category' => null,
            'sort_order' => 1,
            'is_required' => true,
            'metadata' => null,
            'source' => QuestionSource::Manual,
            'import_batch' => null,
            'import_row' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }

    public function forVersion(AssessmentVersion $version, int $sortOrder = 1): static
    {
        return $this->state(fn () => ['assessment_version_id' => $version->id, 'sort_order' => $sortOrder]);
    }

    /**
     * @param  list<string>  $options
     */
    public function singleChoice(array $options = ['Optional Practical Training', 'Occupational Practical Training', 'Optional Professional Training'], string $correct = 'A'): static
    {
        return $this->withOptions(QuestionType::SingleChoice, $options, [$correct]);
    }

    /**
     * @param  list<string>  $options
     * @param  list<string>  $correct
     */
    public function multipleChoice(array $options = ['F-1', 'H-1B', 'B-2', 'L-1'], array $correct = ['A', 'C']): static
    {
        return $this->withOptions(QuestionType::MultipleChoice, $options, $correct);
    }

    public function trueFalse(bool $answer = true): static
    {
        return $this->withOptions(QuestionType::TrueFalse, [QuestionType::TRUE_LABEL, QuestionType::FALSE_LABEL], [$answer ? 'A' : 'B']);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn () => ['type' => QuestionType::ShortAnswer]);
    }

    /**
     * @param  list<string>  $options
     * @param  list<string>  $correct
     */
    protected function withOptions(QuestionType $type, array $options, array $correct): static
    {
        return $this->state(fn () => ['type' => $type])
            ->afterCreating(function (AssessmentQuestion $question) use ($options, $correct) {
                $question->options()->delete();

                foreach ($options as $index => $text) {
                    $key = chr(ord('A') + $index);
                    $option = new AssessmentOption;
                    $option->forceFill([
                        'question_id' => $question->id,
                        'option_key' => $key,
                        'text' => $text,
                        'is_correct' => in_array($key, $correct, true),
                        'sort_order' => $index + 1,
                    ])->save();
                }

                $question->unsetRelation('options');
            });
    }
}
