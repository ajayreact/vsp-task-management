<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\AssignmentMode;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TaskType;
use Database\Factories\TaskManagement\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A unit of internal work. Status and assignment are owned by TaskWorkflow.
 *
 * @property int $id
 * @property int $tm_project_id
 * @property int|null $department_id
 * @property string $title
 * @property string|null $description
 * @property string|null $requirement
 * @property TaskType $type
 * @property TaskPriority $priority
 * @property TaskStatus $status
 * @property AssignmentMode $assignment_mode
 * @property int|null $assigned_employee_id
 * @property int $created_by_user_id
 * @property string|null $estimated_hours
 * @property Carbon|null $due_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property int|null $tm_recurrence_rule_id
 * @property int|null $recurrence_occurrence_number
 * @property-read Project $project
 * @property-read Department|null $department
 * @property-read Employee|null $assignee
 * @property-read User $creator
 */
class Task extends Model implements HasMedia
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, InteractsWithMedia;

    protected $table = 'tm_tasks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_project_id',
        'department_id',
        'title',
        'description',
        'requirement',
        'type',
        'priority',
        'status',
        'assignment_mode',
        'assigned_employee_id',
        'created_by_user_id',
        'estimated_hours',
        'due_at',
        'started_at',
        'completed_at',
        'tm_recurrence_rule_id',
        'recurrence_occurrence_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'assignment_mode' => AssignmentMode::class,
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'tm_project_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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
     * @return HasMany<TaskAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'tm_task_id');
    }

    /**
     * The offer the current holder is answering, if there is one outstanding.
     *
     * @return HasOne<TaskAssignment, $this>
     */
    public function pendingAssignment(): HasOne
    {
        return $this->hasOne(TaskAssignment::class, 'tm_task_id')
            ->where('status', AssignmentStatus::Pending)
            ->latestOfMany();
    }

    /**
     * @return HasMany<TaskStatusChange, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(TaskStatusChange::class, 'tm_task_id');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'tm_task_id');
    }

    /**
     * @return HasMany<Deliverable, $this>
     */
    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class, 'tm_task_id');
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'tm_task_id');
    }

    /**
     * @return HasMany<TaskChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class, 'tm_task_id');
    }

    /**
     * @return HasMany<TaskSubtask, $this>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class, 'tm_task_id');
    }

    /**
     * @return HasMany<TaskReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class, 'tm_task_id');
    }

    /**
     * @return BelongsTo<TaskRecurrenceRule, $this>
     */
    public function recurrenceRule(): BelongsTo
    {
        return $this->belongsTo(TaskRecurrenceRule::class, 'tm_recurrence_rule_id');
    }

    /**
     * @return HasOne<TaskRecurrenceRule, $this>
     */
    public function ownedRecurrenceRule(): HasOne
    {
        return $this->hasOne(TaskRecurrenceRule::class, 'source_tm_task_id');
    }

    public function isRecurrenceSource(): bool
    {
        return $this->recurrence_occurrence_number === 0 || $this->ownedRecurrenceRule()->exists();
    }

    /**
     * Unclaimed work on the open board.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeClaimable(Builder $query): void
    {
        $query->where('status', TaskStatus::Open)
            ->where('assignment_mode', AssignmentMode::Open)
            ->whereNull('assigned_employee_id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAssignedTo(Builder $query, Employee $employee): void
    {
        $query->where('assigned_employee_id', $employee->id);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeStillOpen(Builder $query): void
    {
        $query->whereNot('status', TaskStatus::Completed);
    }

    /**
     * Default ordering for the Tasks list and exports.
     *
     * Combined lists keep completed work at the bottom. Active tasks sort by
     * newest first (created_at). Completed tasks sort by completion time.
     * When a status filter is applied, only that segment's ordering applies.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOrderedForList(Builder $query, ?string $statusFilter = null, bool $onlyCompleted = false): void
    {
        $completed = TaskStatus::Completed->value;

        if ($onlyCompleted || $statusFilter === $completed) {
            $query
                ->orderByRaw('coalesce(completed_at, updated_at) desc')
                ->orderByDesc('id');

            return;
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            return;
        }

        $query
            ->orderByRaw('case when status = ? then 1 else 0 end', [$completed])
            ->orderByRaw('case when status <> ? then created_at end desc', [$completed])
            ->orderByRaw('case when status = ? then coalesce(completed_at, updated_at) end desc', [$completed])
            ->orderByDesc('id');
    }

    /**
     * Active work past its due date.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [TaskStatus::Completed, TaskStatus::Draft]);
    }

    /**
     * Draft work that still needs an owner.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeNeedsAssignment(Builder $query): void
    {
        $query->whereNull('assigned_employee_id')
            ->where('status', TaskStatus::Draft);
    }

    /**
     * Working files on the task itself. Creative proofs live on Deliverable
     * (`proofs`) and must never be mixed into this collection.
     *
     * @return list<string>
     */
    public static function attachmentExtensions(): array
    {
        return [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf',
            'doc', 'docx',
            'xls', 'xlsx', 'csv',
            'ppt', 'pptx',
            'zip',
            'txt', 'rtf',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }
}
