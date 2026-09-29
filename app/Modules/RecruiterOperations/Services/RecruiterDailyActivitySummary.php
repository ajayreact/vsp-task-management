<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;

/**
 * What a recruiter logged today, for their own dashboard. Plain sums of what
 * they reported; no comparison with anyone else and no score.
 */
class RecruiterDailyActivitySummary
{
    /**
     * @return array{count: int, reported_quantity: int|null, recorded_minutes: int|null, recent: list<array<string, mixed>>}
     */
    public function todayFor(Employee $employee): array
    {
        $today = fn () => RecruiterDailyActivity::query()->forEmployee($employee)->forDate(today());

        $totals = $today()
            ->selectRaw('count(*) as activities, sum(quantity) as quantity, sum(duration_minutes) as minutes')
            ->toBase()
            ->first();

        return [
            'count' => (int) ($totals->activities ?? 0),
            'reported_quantity' => $totals?->quantity !== null ? (int) $totals->quantity : null,
            'recorded_minutes' => $totals?->minutes !== null ? (int) $totals->minutes : null,
            'recent' => $today()
                ->with('task:id,title')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (RecruiterDailyActivity $activity) => [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'activity_type_label' => $activity->activity_type->label(),
                    'start_time' => RecruiterDailyActivity::clock($activity->start_time),
                    'end_time' => RecruiterDailyActivity::clock($activity->end_time),
                    'duration_minutes' => $activity->duration_minutes,
                    'quantity' => $activity->quantity,
                    'task_title' => $activity->task?->title,
                ])
                ->values()
                ->all(),
        ];
    }
}
