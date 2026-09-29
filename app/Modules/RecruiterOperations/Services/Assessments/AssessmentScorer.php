<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\QuestionType;

/**
 * Pure scoring rules. Used only on the server.
 *
 * - single choice and true/false: full points for the one correct option,
 *   otherwise 0;
 * - multiple choice: full points only when the chosen set exactly matches the
 *   correct set, otherwise 0 (no partial credit);
 * - short answer: not scored here; a reviewer awards points.
 *
 * A result passes when awarded / total >= passing percentage, compared in
 * whole numbers so rounding can never tip it.
 */
final class AssessmentScorer
{
    /**
     * @param  array<int|string>  $ids
     * @return list<int>
     */
    public static function normalizeSelection(array $ids): array
    {
        $normalized = array_values(array_unique(array_map('intval', array_filter($ids, fn ($id) => is_numeric($id) && (int) $id > 0))));
        sort($normalized);

        return $normalized;
    }

    /**
     * @param  array<int|string>  $correct
     * @param  array<int|string>  $selected
     */
    public static function sameSelection(array $correct, array $selected): bool
    {
        return self::normalizeSelection($correct) === self::normalizeSelection($selected);
    }

    /**
     * @param  list<int>  $correctIds
     * @param  list<int>  $selectedIds
     */
    public static function isCorrect(QuestionType $type, array $correctIds, array $selectedIds): bool
    {
        if ($type === QuestionType::ShortAnswer) {
            return false;
        }

        $correct = self::normalizeSelection($correctIds);
        $selected = self::normalizeSelection($selectedIds);

        if ($correct === [] || $selected === []) {
            return false;
        }

        if (! $type->allowsSeveralCorrect() && count($selected) !== 1) {
            return false;
        }

        return $correct === $selected;
    }

    /**
     * @param  list<int>  $correctIds
     * @param  list<int>  $selectedIds
     */
    public static function pointsFor(QuestionType $type, int $points, array $correctIds, array $selectedIds): int
    {
        return self::isCorrect($type, $correctIds, $selectedIds) ? $points : 0;
    }

    /**
     * Percentage rounded to two decimals, for display.
     */
    public static function percentage(int $awarded, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(min($awarded, $total) * 100 / $total, 2);
    }

    public static function passed(int $awarded, int $total, int $passingPercentage): bool
    {
        return $total > 0 && $awarded * 100 >= $passingPercentage * $total;
    }

    public static function result(int $awarded, int $total, int $passingPercentage, bool $pendingReview): AssessmentResult
    {
        if ($pendingReview) {
            return AssessmentResult::PendingReview;
        }

        return self::passed($awarded, $total, $passingPercentage) ? AssessmentResult::Passed : AssessmentResult::Failed;
    }
}
