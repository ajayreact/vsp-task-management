<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use Carbon\CarbonInterface;
use Database\Factories\RecruiterOperations\AssessmentAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An assessment version given to one recruiter, pinned to that version for
 * good. Every column is written by AssessmentAssignmentService and
 * AssessmentAttemptService; nothing comes from the client.
 *
 * @property int $id
 * @property int $assessment_version_id
 * @property int $employee_id
 * @property int|null $assigned_by_user_id
 * @property int|null $training_assignment_id
 * @property Carbon|null $due_at
 * @property AssessmentAssignmentStatus $status
 * @property AssessmentResult|null $result
 * @property Carbon $assigned_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read AssessmentVersion $version
 * @property-read Employee $employee
 * @property-read User|null $assignedBy
 * @property-read TrainingAssignment|null $trainingAssignment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssessmentAttempt> $attempts
 */
class AssessmentAssignment extends Model
{
    /** @use HasFactory<AssessmentAssignmentFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ro_assessment_assignments';

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
            'status' => AssessmentAssignmentStatus::class,
            'result' => AssessmentResult::class,
            'due_at' => 'datetime',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AssessmentVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'assessment_version_id');
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
     * @return BelongsTo<TrainingAssignment, $this>
     */
    public function trainingAssignment(): BelongsTo
    {
        return $this->belongsTo(TrainingAssignment::class, 'training_assignment_id');
    }

    /**
     * @return HasMany<AssessmentAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class, 'assignment_id')->orderBy('attempt_number');
    }

    public function isOwnedBy(?Employee $employee): bool
    {
        return $employee !== null && $this->employee_id === $employee->id;
    }

    public function isCompleted(): bool
    {
        return $this->status === AssessmentAssignmentStatus::Completed;
    }

    public function isStarted(): bool
    {
        return $this->started_at !== null || $this->status !== AssessmentAssignmentStatus::Assigned;
    }

    public function effectiveStatus(?CarbonInterface $now = null): AssessmentAssignmentStatus
    {
        if ($this->isCompleted()) {
            return AssessmentAssignmentStatus::Completed;
        }

        if ($this->due_at !== null && $this->due_at->lessThan($now ?? now())) {
            return AssessmentAssignmentStatus::Overdue;
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
    public function scopeForAssessment(Builder $query, Assessment|int $assessment): void
    {
        $assessmentId = $assessment instanceof Assessment ? $assessment->id : $assessment;

        $query->whereHas('version', fn (Builder $version) => $version->where('assessment_id', $assessmentId));
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeWithEffectiveStatus(Builder $query, AssessmentAssignmentStatus $status): void
    {
        match ($status) {
            AssessmentAssignmentStatus::Completed => $query->where('status', AssessmentAssignmentStatus::Completed->value),
            AssessmentAssignmentStatus::Overdue => $query->where('status', '!=', AssessmentAssignmentStatus::Completed->value)
                ->whereNotNull('due_at')->where('due_at', '<', now()),
            default => $query->where('status', $status->value)
                ->where(fn (Builder $inner) => $inner->whereNull('due_at')->orWhere('due_at', '>=', now())),
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-assessments')
            ->logOnly(['assessment_version_id', 'employee_id', 'due_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
