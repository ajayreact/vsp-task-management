<?php

namespace App\Modules\RecruiterOperations\Services\Assessments\Import;

use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Services\Assessments\CorrectAnswerParser;
use App\Modules\RecruiterOperations\Services\Assessments\QuestionRules;

/**
 * Checks one spreadsheet row and turns it into a question. Pure: no database.
 * Every problem in the row is reported, not only the first.
 *
 * @phpstan-type ParsedQuestion array{type: string, prompt: string, points: int, explanation: string|null, category: string|null, options: list<array{text: string, correct: bool}>}
 * @phpstan-type RowResult array{row: int, status: string, errors: list<string>, duplicate: string|null, hash: string|null, question: ParsedQuestion|null, values: array<string, string>}
 */
final class QuestionImportRowValidator
{
    public const MAX_PROMPT_LENGTH = 2000;

    public const MAX_TEXT_LENGTH = 5000;

    public const EXAMPLE_PREFIX = 'EXAMPLE:';

    /**
     * @param  array<string, string>  $values  cell text keyed by lower-case header
     * @param  list<string>  $optionLetters  option columns present in the sheet, in order
     * @return RowResult
     */
    public static function validate(int $rowNumber, array $values, array $optionLetters): array
    {
        $errors = [];
        $prompt = trim($values['question'] ?? '');
        $typeText = trim($values['type'] ?? '');
        $type = QuestionType::fromLoose($typeText);

        if ($prompt === '') {
            $errors[] = 'Question is missing.';
        } elseif (mb_strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            $errors[] = 'Question is longer than '.self::MAX_PROMPT_LENGTH.' characters.';
        } elseif (str_starts_with(mb_strtoupper($prompt), self::EXAMPLE_PREFIX)) {
            $errors[] = 'This is an example row from the template. Delete it before importing.';
        }

        if ($typeText === '') {
            $errors[] = 'Type is missing.';
        } elseif ($type === null) {
            $errors[] = 'Type "'.$typeText.'" is not recognised. Use single_choice, multiple_choice, true_false or short_answer.';
        }

        [$options, $optionErrors] = self::options($values, $optionLetters, $type);
        array_push($errors, ...$optionErrors);
        $marked = [];

        if ($type !== null) {
            $parsed = CorrectAnswerParser::parse($values['correct answer'] ?? '', $type, array_map('strval', array_keys($options)));

            if ($parsed['error'] !== null) {
                $errors[] = $parsed['error'];
            }

            foreach ($options as $key => $text) {
                $marked[] = ['text' => $text, 'correct' => in_array((string) $key, $parsed['keys'], true)];
            }

            foreach (QuestionRules::problems($type, $marked) as $problem) {
                if ($parsed['error'] !== null && str_contains($problem, 'correct')) {
                    continue;
                }

                $errors[] = $problem;
            }
        }

        [$points, $pointsError] = self::points($values['points'] ?? '');

        if ($pointsError !== null) {
            $errors[] = $pointsError;
        }

        $explanation = trim($values['explanation'] ?? '');

        if (mb_strlen($explanation) > self::MAX_TEXT_LENGTH) {
            $errors[] = 'Explanation is longer than '.self::MAX_TEXT_LENGTH.' characters.';
        }

        $category = QuestionRules::normalizeCategory($values['category'] ?? null);

        if ($category !== null && mb_strlen($category) > QuestionRules::MAX_CATEGORY_LENGTH) {
            $errors[] = 'Category is longer than '.QuestionRules::MAX_CATEGORY_LENGTH.' characters.';
        }

        $errors = array_values(array_unique($errors));
        $valid = $errors === [] && $type !== null;

        return [
            'row' => $rowNumber,
            'status' => $valid ? 'valid' : 'error',
            'errors' => $errors,
            'duplicate' => null,
            'hash' => $prompt === '' ? null : AssessmentQuestion::hashPrompt($prompt),
            'question' => $valid ? [
                'type' => $type->value,
                'prompt' => $prompt,
                'points' => $points,
                'explanation' => $explanation === '' ? null : $explanation,
                'category' => $category,
                'options' => $type->usesOptions() ? $marked : [],
            ] : null,
            'values' => $values,
        ];
    }

    /**
     * A row with every cell empty is skipped, not reported.
     *
     * @param  array<string, string>  $values
     */
    public static function isBlank(array $values): bool
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Blank means 1 point. Otherwise a whole number from 1 to the maximum.
     *
     * @return array{0: int, 1: string|null}
     */
    public static function points(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [1, null];
        }

        if (! is_numeric($value) || (float) $value !== floor((float) $value)) {
            return [1, 'Points must be a whole number.'];
        }

        $points = (int) $value;

        if ($points < 1 || $points > QuestionRules::MAX_POINTS) {
            return [1, 'Points must be between 1 and '.QuestionRules::MAX_POINTS.'.'];
        }

        return [$points, null];
    }

    /**
     * Options keyed by letter. They must be filled in order (no gaps).
     * True/false rows may leave them empty; True and False are filled in.
     *
     * @param  array<string, string>  $values
     * @param  list<string>  $letters
     * @return array{0: array<string, string>, 1: list<string>}
     */
    public static function options(array $values, array $letters, ?QuestionType $type): array
    {
        $options = [];
        $errors = [];
        $gap = null;

        foreach ($letters as $letter) {
            $text = trim($values['option '.strtolower($letter)] ?? '');

            if ($text === '') {
                $gap ??= $letter;

                continue;
            }

            if ($gap !== null) {
                $errors[] = "Options must be filled in order: Option {$gap} is empty but Option {$letter} is filled.";
                $gap = null;
            }

            if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
                $errors[] = "Option {$letter} is longer than ".self::MAX_TEXT_LENGTH.' characters.';
            }

            $options[$letter] = $text;
        }

        if ($type === QuestionType::TrueFalse && $options === []) {
            $options = ['A' => QuestionType::TRUE_LABEL, 'B' => QuestionType::FALSE_LABEL];
        }

        return [$options, array_values(array_unique($errors))];
    }
}
