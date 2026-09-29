<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;
use App\Support\TabularExporter;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruiterDailyActivityExporter
{
    public function __construct(private readonly TabularExporter $exporter) {}

    /**
     * @param  Collection<int, RecruiterDailyActivity>  $activities
     */
    public function excel(Collection $activities): StreamedResponse
    {
        return $this->exporter->excel(
            'Recruiter Daily Activities',
            $this->headers(),
            $this->rows($activities),
            'recruiter-daily-activities-'.now()->format('Y-m-d-His'),
        );
    }

    /**
     * @return list<string>
     */
    private function headers(): array
    {
        return [
            'Date', 'Recruiter', 'Activity type', 'Title', 'Description', 'Start time',
            'End time', 'Duration (minutes)', 'Quantity', 'Related task', 'Remarks',
        ];
    }

    /**
     * @param  Collection<int, RecruiterDailyActivity>  $activities
     * @return list<list<string|int|null>>
     */
    private function rows(Collection $activities): array
    {
        return $activities->map(fn (RecruiterDailyActivity $activity) => [
            $activity->activity_date->toDateString(),
            $activity->employee->user->name ?? '',
            $activity->activity_type->label(),
            $activity->title,
            $activity->description ?? '',
            RecruiterDailyActivity::clock($activity->start_time) ?? '',
            RecruiterDailyActivity::clock($activity->end_time) ?? '',
            $activity->duration_minutes,
            $activity->quantity,
            $activity->task->title ?? '',
            $activity->remarks ?? '',
        ])->values()->all();
    }
}
