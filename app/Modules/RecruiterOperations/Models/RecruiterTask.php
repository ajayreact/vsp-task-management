<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskPriority;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use App\Modules\RecruiterOperations\Enums\RecruiterWorkType;
use Database\Factories\RecruiterOperations\RecruiterTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Work assigned to a US IT staffing recruiter. Separate from Digital Marketing
 * tasks. Status, assignee and the workflow timestamps are owned by
 * RecruiterTaskWorkflow; the history lives in ro_task_events.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $instructions
 * @property RecruiterWorkType $work_type
 * @property RecruiterTaskPriority $priority
 * @property RecruiterTaskStatus $status
 * @property int|null $assigned_employee_id
 * @property int|null $created_by_user_id
 * @property Carbon|null $due_at
 * @property int|null $target_count
 * @property int|null $achieved_count
 * @property string|null $completion_note
 * @property Carbon|null $accepted_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Employee|null $assignee
 * @property-read User|null $creator
 */
class RecruiterTask extends Model
{
    /** @use HasFactory<RecruiterTaskFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ro_tasks';

    /**
     * Descriptive fields only. Status, assignee and workflow timestamps are
     * written by RecruiterTaskWorkflow through forceFill.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'instructions',
        'work_type',
        'priority',
        'due_at',
        'target_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_type' => RecruiterWorkType::class,
            'priority' => RecruiterTaskPriority::class,
            'status' => RecruiterTaskStatus::class,
            'due_at' => 'datetime',
            'target_count' => 'integer',
            'achieved_count' => 'integer',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<RecruiterTaskEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(RecruiterTaskEvent::class, 'ro_task_id');
    }

    /**
     * Activities recruiters chose to log against this task. Logged by hand,
     * never created by the task workflow.
     *
     * @return HasMany<RecruiterDailyActivity, $this>
     */
    public function dailyActivities(): HasMany
    {
        return $this->hasMany(RecruiterDailyActivity::class, 'recruiter_task_id');
    }

    public function isAssignedTo(?Employee $employee): bool
    {
        return $employee !== null
            && $this->assigned_employee_id !== null
            && $this->assigned_employee_id === $employee->id;
    }

    /**
     * Still in its initial assigned state with nothing but setup on the
     * timeline. Once the workflow has been used, the task and its history are
     * kept and it can only be cancelled.
     */
    public function isDeletable(): bool
    {
        return $this->status === RecruiterTaskStatus::Assigned
            && $this->accepted_at === null
            && ! $this->events()
                ->whereNotIn('event', array_map(
                    fn (RecruiterTaskEventType $type) => $type->value,
                    RecruiterTaskEventType::setupEvents(),
                ))
                ->exists();
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForEmployee(Builder $query, Employee|int $employee): void
    {
        $query->where('assigned_employee_id', $employee instanceof Employee ? $employee->id : $employee);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', [RecruiterTaskStatus::Completed->value, RecruiterTaskStatus::Cancelled->value]);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', RecruiterTaskStatus::pendingValues());
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeDueToday(Builder $query): void
    {
        $query->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]);
    }

    /**
     * Configuration changes only. Workflow history belongs to ro_task_events
     * and is deliberately not duplicated here.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'due_at', 'priority', 'target_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
