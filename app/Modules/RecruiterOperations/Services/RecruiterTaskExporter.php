<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Support\TabularExporter;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruiterTaskExporter
{
    public function __construct(private readonly TabularExporter $exporter) {}

    /**
     * @param  Collection<int, RecruiterTask>  $tasks
     */
    public function excel(Collection $tasks): StreamedResponse
    {
        return $this->exporter->excel('Recruiter Tasks', $this->headers(), $this->rows($tasks), $this->filename());
    }

    /**
     * @param  Collection<int, RecruiterTask>  $tasks
     */
    public function pdf(Collection $tasks)
    {
        return $this->exporter->pdf('Recruiter Tasks', $this->headers(), $this->rows($tasks), $this->filename());
    }

    /**
     * @return list<string>
     */
    private function headers(): array
    {
        return ['Task', 'Recruiter', 'Work type', 'Priority', 'Status', 'Due', 'Target', 'Achieved', 'Completed'];
    }

    /**
     * @param  Collection<int, RecruiterTask>  $tasks
     * @return list<list<string|int|null>>
     */
    private function rows(Collection $tasks): array
    {
        $timezone = config('app.timezone');

        return $tasks->map(fn (RecruiterTask $task) => [
            $task->title,
            $task->assignee->user->name ?? 'Unassigned',
            $task->work_type->label(),
            $task->priority->label(),
            $task->status->label(),
            $task->due_at?->timezone($timezone)->format('Y-m-d H:i') ?? '',
            $task->target_count,
            $task->achieved_count,
            $task->completed_at?->timezone($timezone)->format('Y-m-d H:i') ?? '',
        ])->values()->all();
    }

    private function filename(): string
    {
        return 'recruiter-tasks-'.now()->format('Y-m-d-His');
    }
}
