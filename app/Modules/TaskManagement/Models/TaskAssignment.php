<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\AssignmentAction;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_task_id
 * @property int $employee_id
 * @property int|null $assigned_by_user_id
 * @property AssignmentAction $mode
 * @property AssignmentStatus $status
 * @property Carbon|null $responded_at
 * @property string|null $decline_reason
 * @property-read Task $task
 * @property-read Employee $employee
 * @property-read User|null $assignedBy
 */
class TaskAssignment extends Model
{
    protected $table = 'tm_task_assignments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_task_id',
        'employee_id',
        'assigned_by_user_id',
        'mode',
        'status',
        'responded_at',
        'decline_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => AssignmentAction::class,
            'status' => AssignmentStatus::class,
            'responded_at' => 'datetime',
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
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
