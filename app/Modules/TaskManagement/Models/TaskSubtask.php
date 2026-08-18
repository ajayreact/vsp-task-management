<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One-level child work item on a parent task. No nested subtasks.
 *
 * @property int $id
 * @property int $tm_task_id
 * @property string $title
 * @property string|null $description
 * @property SubtaskStatus $status
 * @property int|null $assigned_employee_id
 * @property Carbon|null $due_at
 * @property Carbon|null $completed_at
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Task $task
 * @property-read Employee|null $assignee
 */
class TaskSubtask extends Model
{
    protected $table = 'tm_task_subtasks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_task_id',
        'title',
        'description',
        'status',
        'assigned_employee_id',
        'due_at',
        'completed_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubtaskStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tm_task_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }
}
