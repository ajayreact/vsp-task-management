<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\PersonalTodoStatus;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\PersonalTodo;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskSubtask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MyTodoService
{
    /**
     * Compact dashboard payload for the My Today widget.
     *
     * @return array<string, mixed>|null
     */
    public function dashboardSnapshot(User $user): ?array
    {
        $employee = $user->employee;

        if ($employee === null) {
            return null;
        }

        $items = $this->collectItems($user, $employee);
        $grouped = $this->groupItems($items);

        $todayActive = $grouped['today']['items']->filter(fn (array $item) => ! $item['is_completed']);
        $todayCompleted = $grouped['today']['items']->filter(fn (array $item) => $item['is_completed']);
        $todayTotal = $todayActive->count() + $todayCompleted->count();
        $todayDone = $todayCompleted->count();

        return [
            'greeting' => $this->greeting($user),
            'today' => [
                'count' => $grouped['today']['count'],
                'items' => $grouped['today']['items']->take(5)->values()->all(),
            ],
            'overdue' => [
                'count' => $grouped['overdue']['count'],
                'items' => $grouped['overdue']['items']->take(3)->values()->all(),
            ],
            'upcoming' => [
                'count' => $grouped['upcoming']['count'],
                'groups' => collect($grouped['upcoming']['groups'])
                    ->map(fn (array $group) => [
                        'label' => $group['label'],
                        'count' => $group['count'],
                        'items' => collect($group['items'])->take(2)->values()->all(),
                    ])
                    ->take(3)
                    ->values()
                    ->all(),
            ],
            'completed_today' => [
                'count' => $grouped['completed_today']['count'],
                'items' => $grouped['completed_today']['items']->take(3)->values()->all(),
            ],
            'progress' => [
                'completed' => $todayDone,
                'total' => max($todayTotal, 1),
                'overdue_count' => $grouped['overdue']['count'],
                'due_today_count' => $grouped['today']['count'],
                'completed_today_count' => $grouped['completed_today']['count'],
            ],
            'href' => '/tasks/todos',
            'priorities' => TaskPriority::options(),
        ];
    }

    /**
     * Full My Todos page payload.
     *
     * @param  array{tab?: string, priority?: string, date?: string, project?: int|null, client?: int|null, search?: string}  $filters
     * @return array<string, mixed>
     */
    public function pagePayload(User $user, array $filters = []): array
    {
        $employee = $user->employee;
        $items = $employee ? $this->collectItems($user, $employee) : collect();
        $grouped = $this->groupItems($items);
        $filtered = $this->applyFilters($items, $filters);

        return [
            'greeting' => $this->greeting($user),
            'filters' => [
                'tab' => $filters['tab'] ?? 'all',
                'priority' => $filters['priority'] ?? '',
                'date' => $filters['date'] ?? '',
                'project' => $filters['project'] ?? null,
                'client' => $filters['client'] ?? null,
                'search' => $filters['search'] ?? '',
            ],
            'sections' => [
                'today' => $this->sectionPayload($grouped['today']),
                'overdue' => $this->sectionPayload($grouped['overdue']),
                'upcoming' => [
                    'count' => $grouped['upcoming']['count'],
                    'groups' => $grouped['upcoming']['groups'],
                ],
                'completed_today' => $this->sectionPayload($grouped['completed_today']),
            ],
            'items' => $filtered->values()->all(),
            'counts' => [
                'all' => $items->count(),
                'today' => $grouped['today']['count'],
                'overdue' => $grouped['overdue']['count'],
                'upcoming' => $grouped['upcoming']['count'],
                'completed' => $grouped['completed_today']['count'],
            ],
            'priorities' => TaskPriority::options(),
            'projects' => $this->projectOptions($items),
            'clients' => $this->clientOptions($items),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectItems(User $user, Employee $employee): Collection
    {
        $personal = PersonalTodo::query()
            ->forUser($user)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PersonalTodo $todo) => $this->personalTodoItem($todo));

        $tasks = $this->assignedTasksQuery($employee)
            ->get()
            ->flatMap(fn (Task $task) => $this->taskItems($task, $employee));

        return $this->sortItems($personal->concat($tasks));
    }

    /**
     * @return Builder<Task>
     */
    protected function assignedTasksQuery(Employee $employee): Builder
    {
        return Task::query()
            ->with([
                'project:id,name,tm_company_id',
                'project.company:id,name',
                'checklistItems:id,tm_task_id,is_completed',
                'subtasks' => fn ($query) => $query
                    ->where('assigned_employee_id', $employee->id)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->where(function (Builder $query) use ($employee) {
                $query->where('assigned_employee_id', $employee->id)
                    ->orWhereHas('subtasks', fn (Builder $subtasks) => $subtasks
                        ->where('assigned_employee_id', $employee->id)
                        ->where('status', '!=', SubtaskStatus::Completed));
            })
            ->where(function (Builder $query) {
                $query->whereNot('status', TaskStatus::Completed)
                    ->orWhereDate('completed_at', today());
            });
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function taskItems(Task $task, Employee $employee): array
    {
        $checklistCompleted = $task->checklistItems->where('is_completed', true)->count();
        $checklistTotal = $task->checklistItems->count();
        $isAssignee = $task->assigned_employee_id === $employee->id;
        $isCompleted = $task->status === TaskStatus::Completed;
        $dueAt = $task->due_at;
        $subtaskModels = $task->subtasks
            ->filter(fn (TaskSubtask $subtask) => ! $subtask->status->isCompleted());
        $subtasks = $subtaskModels
            ->map(fn (TaskSubtask $subtask) => [
                'key' => "subtask:{$subtask->id}",
                'title' => $subtask->title,
                'href' => "/tasks/{$task->id}",
            ])
            ->values()
            ->all();

        if (! $isAssignee && $subtasks === []) {
            return [];
        }

        if ($isAssignee || $subtasks !== []) {
            return [[
                'key' => "task:{$task->id}",
                'source' => 'task',
                'id' => $task->id,
                'task_id' => $task->id,
                'parent_task_id' => null,
                'title' => $task->title,
                'subtitle' => $task->project->name,
                'client_name' => $task->project->company?->name,
                'project_id' => $task->project->id,
                'client_id' => $task->project->tm_company_id,
                'priority' => $task->priority->value,
                'priority_label' => $task->priority->label(),
                'priority_weight' => $task->priority->weight(),
                'due_at' => $dueAt?->toIso8601String(),
                'due_date' => $dueAt?->toDateString(),
                'due_time' => $dueAt?->format('H:i'),
                'is_overdue' => ! $isCompleted && $dueAt !== null && $dueAt->isPast(),
                'is_due_today' => $dueAt !== null && $dueAt->isToday(),
                'is_completed' => $isCompleted,
                'completed_at' => $task->completed_at?->toIso8601String(),
                'href' => "/tasks/{$task->id}",
                'kind_label' => 'Task',
                'checklist' => $checklistTotal > 0 ? [
                    'completed' => $checklistCompleted,
                    'total' => $checklistTotal,
                ] : null,
                'subtasks' => $subtasks,
                'can_complete' => false,
                'can_move_to_today' => false,
                'note' => null,
                'sort_order' => null,
                'created_at' => $task->created_at?->toIso8601String(),
            ]];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function personalTodoItem(PersonalTodo $todo): array
    {
        $dueAt = $todo->effectiveDueAt();

        return [
            'key' => "personal:{$todo->id}",
            'source' => 'personal',
            'id' => $todo->id,
            'task_id' => $todo->tm_task_id,
            'parent_task_id' => null,
            'title' => $todo->title,
            'subtitle' => null,
            'client_name' => null,
            'project_id' => null,
            'client_id' => null,
            'priority' => $todo->priority->value,
            'priority_label' => $todo->priority->label(),
            'priority_weight' => $todo->priority->weight(),
            'due_at' => $dueAt?->toIso8601String(),
            'due_date' => $todo->due_date?->toDateString(),
            'due_time' => $todo->due_time,
            'is_overdue' => $todo->isOverdue(),
            'is_due_today' => $todo->isDueToday(),
            'is_completed' => $todo->status->isCompleted(),
            'completed_at' => $todo->completed_at?->toIso8601String(),
            'href' => '/tasks/todos',
            'kind_label' => 'Todo',
            'checklist' => null,
            'subtasks' => [],
            'can_complete' => true,
            'can_move_to_today' => $todo->isOverdue(),
            'note' => $todo->note,
            'sort_order' => $todo->sort_order,
            'created_at' => $todo->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    protected function groupItems(Collection $items): array
    {
        $active = $items->filter(fn (array $item) => ! $item['is_completed']);
        $completedToday = $items->filter(function (array $item) {
            if (! $item['is_completed'] || $item['completed_at'] === null) {
                return false;
            }

            return Carbon::parse($item['completed_at'])->isToday();
        });

        $overdue = $active->filter(fn (array $item) => $item['is_overdue']);
        $today = $active->filter(fn (array $item) => $item['is_due_today'] && ! $item['is_overdue']);
        $upcomingItems = $active->filter(function (array $item) {
            if ($item['is_overdue'] || $item['is_due_today']) {
                return false;
            }

            if ($item['due_date'] === null) {
                return false;
            }

            $due = Carbon::parse($item['due_date']);

            return $due->betweenIncluded(today()->addDay(), today()->addDays(7));
        });

        return [
            'today' => [
                'count' => $today->count() + $completedToday->filter(fn (array $item) => $item['is_due_today'] || ($item['due_date'] !== null && Carbon::parse($item['due_date'])->isToday()))->count(),
                'items' => $this->sortItems(
                    $today->concat($completedToday->filter(fn (array $item) => $item['due_date'] !== null && Carbon::parse($item['due_date'])->isToday()))
                ),
            ],
            'overdue' => [
                'count' => $overdue->count(),
                'items' => $this->sortItems($overdue),
            ],
            'upcoming' => [
                'count' => $upcomingItems->count(),
                'groups' => $this->upcomingGroups($upcomingItems),
            ],
            'completed_today' => [
                'count' => $completedToday->count(),
                'items' => $this->sortItems($completedToday),
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return list<array{label: string, count: int, items: list<array<string, mixed>>}>
     */
    protected function upcomingGroups(Collection $items): array
    {
        return $items
            ->groupBy(fn (array $item) => $item['due_date'])
            ->sortKeys()
            ->map(function (Collection $group, string $date) {
                $carbon = Carbon::parse($date);

                return [
                    'label' => $carbon->isTomorrow() ? 'Tomorrow' : $carbon->format('M j'),
                    'count' => $group->count(),
                    'items' => $this->sortItems($group)->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    protected function sortItems(Collection $items): Collection
    {
        return $items->sort(function (array $a, array $b) {
            if ($a['is_completed'] !== $b['is_completed']) {
                return $a['is_completed'] ? 1 : -1;
            }

            if ($a['is_overdue'] !== $b['is_overdue']) {
                return $a['is_overdue'] ? -1 : 1;
            }

            if ($a['is_due_today'] !== $b['is_due_today']) {
                return $a['is_due_today'] ? -1 : 1;
            }

            if ($a['priority_weight'] !== $b['priority_weight']) {
                return $b['priority_weight'] <=> $a['priority_weight'];
            }

            $aDue = $a['due_at'] ? strtotime($a['due_at']) : PHP_INT_MAX;
            $bDue = $b['due_at'] ? strtotime($b['due_at']) : PHP_INT_MAX;

            if ($aDue !== $bDue) {
                return $aDue <=> $bDue;
            }

            $aSort = $a['sort_order'] ?? PHP_INT_MAX;
            $bSort = $b['sort_order'] ?? PHP_INT_MAX;

            if ($aSort !== $bSort) {
                return $aSort <=> $bSort;
            }

            $aCreated = $a['created_at'] ? strtotime($a['created_at']) : 0;
            $bCreated = $b['created_at'] ? strtotime($b['created_at']) : 0;

            return $bCreated <=> $aCreated;
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  array{tab?: string, priority?: string, date?: string, project?: int|null, client?: int|null, search?: string}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function applyFilters(Collection $items, array $filters): Collection
    {
        $tab = $filters['tab'] ?? 'all';

        $filtered = match ($tab) {
            'today' => $items->filter(fn (array $item) => ($item['is_due_today'] || ($item['due_date'] !== null && Carbon::parse($item['due_date'])->isToday())) && ! $item['is_overdue']),
            'overdue' => $items->filter(fn (array $item) => $item['is_overdue'] && ! $item['is_completed']),
            'upcoming' => $items->filter(function (array $item) {
                if ($item['is_completed'] || $item['due_date'] === null || $item['is_overdue'] || $item['is_due_today']) {
                    return false;
                }

                return Carbon::parse($item['due_date'])->betweenIncluded(today()->addDay(), today()->addDays(7));
            }),
            'completed' => $items->filter(function (array $item) {
                return $item['is_completed']
                    && $item['completed_at'] !== null
                    && Carbon::parse($item['completed_at'])->isToday();
            }),
            default => $items->filter(fn (array $item) => ! $item['is_completed']),
        };

        if (($filters['priority'] ?? '') !== '') {
            $filtered = $filtered->filter(fn (array $item) => $item['priority'] === $filters['priority']);
        }

        if (($filters['date'] ?? '') !== '') {
            $filtered = $filtered->filter(fn (array $item) => $item['due_date'] === $filters['date']);
        }

        if (($filters['project'] ?? null) !== null) {
            $filtered = $filtered->filter(fn (array $item) => $item['project_id'] === (int) $filters['project']);
        }

        if (($filters['client'] ?? null) !== null) {
            $filtered = $filtered->filter(fn (array $item) => $item['client_id'] === (int) $filters['client']);
        }

        if (($filters['search'] ?? '') !== '') {
            $needle = mb_strtolower($filters['search']);
            $filtered = $filtered->filter(fn (array $item) => str_contains(mb_strtolower($item['title']), $needle));
        }

        return $this->sortItems($filtered);
    }

    /**
     * @param  array{count: int, items: Collection<int, array<string, mixed>>}  $section
     * @return array{count: int, items: list<array<string, mixed>>}
     */
    protected function sectionPayload(array $section): array
    {
        return [
            'count' => $section['count'],
            'items' => $section['items']->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return list<array{id: int, name: string}>
     */
    protected function projectOptions(Collection $items): array
    {
        return $items
            ->filter(fn (array $item) => $item['project_id'] !== null)
            ->unique('project_id')
            ->map(fn (array $item) => ['id' => $item['project_id'], 'name' => $item['subtitle'] ?? ''])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return list<array{id: int, name: string}>
     */
    protected function clientOptions(Collection $items): array
    {
        return $items
            ->filter(fn (array $item) => $item['client_id'] !== null)
            ->unique('client_id')
            ->map(fn (array $item) => ['id' => $item['client_id'], 'name' => $item['client_name'] ?? ''])
            ->values()
            ->all();
    }

    protected function greeting(User $user): string
    {
        $hour = now()->hour;
        $salutation = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };

        return "{$salutation}, {$user->name}";
    }
}
