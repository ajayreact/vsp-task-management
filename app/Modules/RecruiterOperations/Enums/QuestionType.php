<?php

namespace App\Modules\RecruiterOperations\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    public const TRUE_LABEL = 'True';

    public const FALSE_LABEL = 'False';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Single choice',
            self::MultipleChoice => 'Multiple choice',
            self::TrueFalse => 'True / False',
            self::ShortAnswer => 'Short answer',
        };
    }

    public function usesOptions(): bool
    {
        return $this !== self::ShortAnswer;
    }

    /**
     * Short answers are scored by a reviewer, never by matching text.
     */
    public function needsManualReview(): bool
    {
        return $this === self::ShortAnswer;
    }

    public function allowsSeveralCorrect(): bool
    {
        return $this === self::MultipleChoice;
    }

    /**
     * Accepts the stored value in any case, with spaces, hyphens or slashes
     * ("Single choice", "true/false", "MULTIPLE-CHOICE").
     */
    public static function fromLoose(?string $value): ?self
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = (string) preg_replace('/[\s\-\/]+/', '_', $normalized);

        return self::tryFrom($normalized);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
