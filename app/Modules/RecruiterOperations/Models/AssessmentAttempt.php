<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\AttemptStatus;
use Carbon\CarbonInterface;
use Database\Factories\RecruiterOperations\AssessmentAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One sitting of an assessment. Records the exact version and the question
 * order shown. Scores are written only by AssessmentAttemptService and
 * AssessmentReviewService, never taken from the browser.
 *
 * @property int $id
 * @property int $assignment_id
 * @property int $assessment_version_id
 * @property int $employee_id
 * @property int $attempt_number
 * @property AttemptStatus $status
 * @property int|null $active_assignment_id
 * @property array{questions: list<int>, options: array<int|string, list<int>>} $layout
 * @property Carbon $started_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $submitted_at
 * @property bool $auto_submitted
 * @property int $total_points
 * @property int|null $awarded_points
 * @property string|null $percentage
 * @property AssessmentResult|null $result
 * @property Carbon|null $scored_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read AssessmentAssignment $assignment
 * @property-read AssessmentVersion $version
 * @property-read Employee $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssessmentAnswer> $answers
 */
class AssessmentAttempt extends Model
{
    /** @use HasFactory<AssessmentAttemptFactory> */
    use HasFactory;

    /**
     * Seconds allowed past the deadline for the final save to arrive.
     */
    public const GRACE_SECONDS = 30;

    protected $table = 'ro_assessment_attempts';

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
            'attempt_number' => 'integer',
            'status' => AttemptStatus::class,
            'result' => AssessmentResult::class,
            'layout' => 'array',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'scored_at' => 'datetime',
            'auto_submitted' => 'boolean',
            'total_points' => 'integer',
            'awarded_points' => 'integer',
            'percentage' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<AssessmentAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AssessmentAssignment::class, 'assignment_id');
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
     * @return HasMany<AssessmentAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class, 'attempt_id');
    }

    public function isInProgress(): bool
    {
        return $this->status === AttemptStatus::InProgress;
    }

    public function isOwnedBy(?Employee $employee): bool
    {
        return $employee !== null && $this->employee_id === $employee->id;
    }

    /**
     * Past the deadline, including the grace period.
     */
    public function hasExpired(?CarbonInterface $now = null): bool
    {
        return $this->expires_at !== null
            && ($now ?? now())->greaterThan($this->expires_at->copy()->addSeconds(self::GRACE_SECONDS));
    }

    public function secondsRemaining(?CarbonInterface $now = null): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return max(0, (int) ($now ?? now())->diffInSeconds($this->expires_at, false));
    }

    public function isPendingReview(): bool
    {
        return $this->result === AssessmentResult::PendingReview;
    }
}
