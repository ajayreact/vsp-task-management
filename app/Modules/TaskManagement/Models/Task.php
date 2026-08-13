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
 * A unit of internal work. Has no relationship to CRM leads or follow-ups: a
 * sales follow-up and a work task are separate concepts, and nothing here may
 * reference a `crm_*` table.
 *
 * @property int $id
 * @property int $tm_project_id
 * @property int|null $department_id
 * @property string $title
 * @property string|null $description
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }
}
