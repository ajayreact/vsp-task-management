<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Models\Task;
use App\Support\TabularExporter;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskListExporter
{
    public function __construct(private readonly TabularExporter $exporter) {}

    /**
     * @param  Collection<int, Task>  $tasks
     */
    public function excel(Collection $tasks): StreamedResponse
    {
        return $this->exporter->excel('Tasks', $this->headers(), $this->rows($tasks), 'tasks-'.now()->format('Y-m-d-His'));
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    public function pdf(Collection $tasks)
    {
        return $this->exporter->pdf('Tasks', $this->headers(), $this->rows($tasks), 'tasks-'.now()->format('Y-m-d-His'));
    }

    /**
     * @return list<string>
     */
    private function headers(): array
    {
        return ['Task', 'Type', 'Project', 'Assignee', 'Priority', 'Status', 'Due'];
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return list<list<string|null>>
     */
    private function rows(Collection $tasks): array
    {
        return $tasks->map(fn (Task $task) => [
            $task->title,
            $task->type->label(),
            $task->project?->name ?? '',
            $task->assignee?->user?->name ?? 'Unassigned',
            $task->priority->label(),
            $task->status->label(),
            $task->due_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '',
        ])->all();
    }
}
