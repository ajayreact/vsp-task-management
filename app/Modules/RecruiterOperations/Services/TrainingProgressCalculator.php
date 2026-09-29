<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\RecruiterOperations\Models\TrainingLesson;

/**
 * Pure progress arithmetic. Progress counts the required lessons; a course
 * with no required lessons counts all of them. A course is complete only when
 * every counted lesson has been marked complete.
 */
final class TrainingProgressCalculator
{
    /**
     * @param  iterable<TrainingLesson>  $lessons
     * @return list<int>
     */
    public static function countedLessonIds(iterable $lessons): array
    {
        $all = [];
        $required = [];

        foreach ($lessons as $lesson) {
            $all[] = $lesson->id;

            if ($lesson->is_required) {
                $required[] = $lesson->id;
            }
        }

        return $required !== [] ? $required : $all;
    }

    /**
     * Whole percent, rounded down so 100 means everything counted is done.
     *
     * @param  list<int>  $countedIds
     * @param  list<int>  $completedIds
     */
    public static function percent(array $countedIds, array $completedIds): int
    {
        if ($countedIds === []) {
            return 0;
        }

        $done = count(array_intersect(array_unique($countedIds), $completedIds));

        return (int) floor($done * 100 / count(array_unique($countedIds)));
    }

    /**
     * @param  list<int>  $countedIds
     * @param  list<int>  $completedIds
     */
    public static function isComplete(array $countedIds, array $completedIds): bool
    {
        return $countedIds !== [] && array_diff($countedIds, $completedIds) === [];
    }
}
