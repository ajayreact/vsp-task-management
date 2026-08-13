<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Department;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Unclaimed work, for employees to pick up themselves. Deliberately a separate
 * screen from the task list: this is the one place where someone sees work that
 * is not theirs and is expected to act on it.
 */
class OpenBoardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();
        $employee = $user->employee;

        $tasks = Task::query()
            ->claimable()
            ->with([
                'project:id,name',
                'department:id,name',
            ])
            ->when($request->integer('department'), fn ($query, int $id) => $query->where('department_id', $id))
            ->when(
                $request->boolean('mine_only') && $employee?->department_id !== null,
                fn ($query) => $query->where('department_id', $employee?->department_id),
            )
            ->orderByRaw("field(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderByRaw('due_at is null, due_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'type' => $task->type->label(),
                'priority' => $task->priority->value,
                'priority_label' => $task->priority->label(),
                'project_name' => $task->project->name,
                'department_name' => $task->department?->name,
                'estimated_hours' => $task->estimated_hours,
                'due_at' => $task->due_at?->toIso8601String(),
            ]);

        return Inertia::render('TaskManagement/board', [
            'tasks' => $tasks,
            'filters' => [
                'department' => $request->integer('department') ?: null,
                'mine_only' => $request->boolean('mine_only'),
            ],
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'can' => [
                // One flag for the whole board: claiming depends on having an
                // employee profile, which does not vary row by row.
                'claim' => $employee !== null,
            ],
        ]);
    }
}
