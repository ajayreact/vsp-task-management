<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use Carbon\CarbonInterface;
use Database\Factories\RecruiterOperations\TrainingAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A course version given to one recruiter. Pinned to that version for good,
 * so publishing a newer version never touches it or its completion history.
 *
 * Every column is written by TrainingAssignmentService and
 * TrainingProgressService; nothing comes from the client.
 *
 * @property int $id
 * @property int $course_version_id
 * @property int $employee_id
 * @property int|null $assigned_by_user_id
 * @property Carbon|null $due_at
 * @property TrainingAssignmentStatus $status
 * @property Carbon $assigned_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read TrainingCourseVersion $version
 * @property-read Employee $employee
 * @property-read User|null $assignedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TrainingLessonCompletion> $completions
 */
class TrainingAssignment extends Model
{
    /** @use HasFactory<TrainingAssignmentFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ro_training_assignments';

    /**
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TrainingAssignmentStatus::class,
            'due_at' => 'datetime',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TrainingCourseVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(TrainingCourseVersion::class, 'course_version_id');
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

    /**
     * @return HasMany<TrainingLessonCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TrainingLessonCompletion::class, 'assignment_id');
    }

    public function isOwnedBy(?Employee $employee): bool
    {
        return $employee !== null && $this->employee_id === $employee->id;
    }

    public function isCompleted(): bool
    {
        return $this->status === TrainingAssignmentStatus::Completed;
    }

    public function isStarted(): bool
    {
        return $this->started_at !== null || $this->status !== TrainingAssignmentStatus::Assigned;
    }

    /**
     * The status to show: the stored one, or Overdue when an open assignment
     * has passed its due date.
     */
    public function effectiveStatus(?CarbonInterface $now = null): TrainingAssignmentStatus
    {
        if ($this->isCompleted()) {
            return TrainingAssignmentStatus::Completed;
        }

        if ($this->due_at !== null && $this->due_at->lessThan($now ?? now())) {
            return TrainingAssignmentStatus::Overdue;
        }

        return $this->status;
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForEmployee(Builder $query, Employee|int $employee): void
    {
        $query->where('employee_id', $employee instanceof Employee ? $employee->id : $employee);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', TrainingAssignmentStatus::Completed->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }

    /**
     * Filters by the status a person sees, so "assigned" and "in_progress"
     * leave out overdue ones.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeWithEffectiveStatus(Builder $query, TrainingAssignmentStatus $status): void
    {
        match ($status) {
            TrainingAssignmentStatus::Completed => $query->where('status', TrainingAssignmentStatus::Completed->value),
            TrainingAssignmentStatus::Overdue => $query->overdue(),
            default => $query->where('status', $status->value)
                ->where(fn (Builder $inner) => $inner->whereNull('due_at')->orWhere('due_at', '>=', now())),
        };
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForCourse(Builder $query, TrainingCourse|int $course): void
    {
        $courseId = $course instanceof TrainingCourse ? $course->id : $course;

        $query->whereHas('version', fn (Builder $version) => $version->where('course_id', $courseId));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-training')
            ->logOnly(['course_version_id', 'employee_id', 'due_at', 'status', 'completed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
