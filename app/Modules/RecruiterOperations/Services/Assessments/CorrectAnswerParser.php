<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\RecruiterOperations\Enums\QuestionType;

/**
 * Reads the "Correct Answer" cell of the import template.
 *
 * - option letters, comma separated: "A" or "A,C" (spaces and case ignored);
 * - true/false may also use the words "True" / "False".
 *
 * Returns the option letters, or an error message.
 */
final class CorrectAnswerParser
{
    /**
     * @param  list<string>  $availableKeys  letters of the options present in the row
     * @return array{keys: list<string>, error: string|null}
     */
    public static function parse(string $value, QuestionType $type, array $availableKeys): array
    {
        $value = trim($value);

        if ($type === QuestionType::ShortAnswer) {
            return $value === ''
                ? ['keys' => [], 'error' => null]
                : ['keys' => [], 'error' => 'Leave Correct Answer empty for short-answer questions; they are reviewed manually.'];
        }

        if ($value === '') {
            return ['keys' => [], 'error' => 'Correct Answer is missing.'];
        }

        if ($type === QuestionType::TrueFalse) {
            $word = mb_strtolower($value);

            if ($word === 'true') {
                return ['keys' => ['A'], 'error' => null];
            }

            if ($word === 'false') {
                return ['keys' => ['B'], 'error' => null];
            }
        }

        if (preg_match('/^[A-Za-z](\s*,\s*[A-Za-z])*$/', $value) !== 1) {
            return ['keys' => [], 'error' => 'Correct Answer must be option letters such as A or A,C.'];
        }

        $keys = array_map(fn (string $key) => strtoupper(trim($key)), explode(',', $value));

        if (count($keys) !== count(array_unique($keys))) {
            return ['keys' => [], 'error' => 'Correct Answer lists the same option twice.'];
        }

        $missing = array_values(array_diff($keys, $availableKeys));

        if ($missing !== []) {
            return ['keys' => [], 'error' => 'Correct Answer refers to option '.implode(', ', $missing).', which is empty.'];
        }

        if (! $type->allowsSeveralCorrect() && count($keys) !== 1) {
            return ['keys' => [], 'error' => $type === QuestionType::TrueFalse
                ? 'True/false questions have exactly one correct answer.'
                : 'Single-choice questions have exactly one correct answer.'];
        }

        return ['keys' => $keys, 'error' => null];
    }
}
