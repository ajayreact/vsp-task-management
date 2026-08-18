<?php

namespace App\Modules\TaskManagement\Support;

use Illuminate\Support\Carbon;

/**
 * Timesheets and the workload board both think in Monday–Sunday weeks.
 */
final class WorkWeek
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    public static function containing(Carbon|string|null $date = null): self
    {
        $day = Carbon::parse($date ?? now())->startOfDay();

        return new self(
            $day->copy()->startOfWeek(Carbon::MONDAY),
            $day->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
        );
    }

    /**
     * @return list<Carbon>
     */
    public function days(): array
    {
        $days = [];
        $cursor = $this->start->copy();

        while ($cursor->lte($this->end)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        return $days;
    }
}
