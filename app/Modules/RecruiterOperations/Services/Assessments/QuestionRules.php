<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\RecruiterOperations\Enums\QuestionType;

/**
 * The shape each question type needs, shared by the question form and the
 * Excel import so both accept exactly the same questions.
 *
 * - single choice: at least 2 options, exactly 1 correct;
 * - multiple choice: at least 2 options, at least 1 correct;
 * - true/false: exactly the options True and False, exactly 1 correct;
 * - short answer: no options (a reviewer scores it).
 */
final class QuestionRules
{
    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 26;

    public const MAX_POINTS = 100;

    public const MAX_CATEGORY_LENGTH = 100;

    /**
     * @param  list<array{text: string, correct: bool}>  $options
     * @return list<string>
     */
    public static function problems(QuestionType $type, array $options): array
    {
        $options = array_values(array_filter($options, fn (array $option) => trim($option['text']) !== ''));
        $correct = count(array_filter($options, fn (array $option) => $option['correct']));

        if ($type === QuestionType::ShortAnswer) {
            return $options === [] ? [] : ['Short-answer questions do not have options; they are reviewed manually.'];
        }

        $problems = [];

        if ($type === QuestionType::TrueFalse) {
            $texts = array_map(fn (array $option) => mb_strtolower(trim($option['text'])), $options);

            if ($texts !== ['true', 'false']) {
                $problems[] = 'True/false questions must have exactly the options True and False.';
            }
        } elseif (count($options) < self::MIN_OPTIONS) {
            $problems[] = 'Add at least '.self::MIN_OPTIONS.' options.';
        } elseif (count($options) > self::MAX_OPTIONS) {
            $problems[] = 'A question can have at most '.self::MAX_OPTIONS.' options.';
        }

        $texts = array_map(fn (array $option) => mb_strtolower(trim($option['text'])), $options);

        if (count($texts) !== count(array_unique($texts))) {
            $problems[] = 'Two options have the same text.';
        }

        if ($type->allowsSeveralCorrect()) {
            if ($correct < 1) {
                $problems[] = 'Mark at least one correct option.';
            }
        } elseif ($correct !== 1) {
            $problems[] = 'Mark exactly one correct option.';
        }

        return $problems;
    }

    /**
     * Trimmed, inner whitespace collapsed; empty becomes null.
     */
    public static function normalizeCategory(?string $category): ?string
    {
        $category = trim((string) preg_replace('/\s+/u', ' ', (string) $category));

        return $category === '' ? null : $category;
    }

    public static function optionKey(int $index): string
    {
        return chr(ord('A') + $index);
    }
}
