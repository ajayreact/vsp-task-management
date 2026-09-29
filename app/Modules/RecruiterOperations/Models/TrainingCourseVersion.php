<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use Database\Factories\RecruiterOperations\TrainingCourseVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One numbered edition of a course's content. Only a draft may change; once
 * published it is frozen, and assignments stay pinned to it for good.
 *
 * @property int $id
 * @property int $course_id
 * @property int $version_number
 * @property TrainingContentStatus $status
 * @property string|null $description
 * @property int|null $estimated_minutes
 * @property Carbon|null $published_at
 * @property int|null $created_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read TrainingCourse $course
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TrainingLesson> $lessons
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TrainingAssignment> $assignments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssessmentVersion> $assessmentVersions
 * @property-read User|null $creator
 */
class TrainingCourseVersion extends Model
{
    /** @use HasFactory<TrainingCourseVersionFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ro_training_course_versions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'description',
        'estimated_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'status' => TrainingContentStatus::class,
            'estimated_minutes' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TrainingCourse, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    /**
     * @return HasMany<TrainingLesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingLesson::class, 'course_version_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<TrainingAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class, 'course_version_id');
    }

    /**
     * Quizzes attached to this version. Only a reference: the questions and
     * results live in the assessment tables.
     *
     * @return BelongsToMany<AssessmentVersion, $this>
     */
    public function assessmentVersions(): BelongsToMany
    {
        return $this->belongsToMany(AssessmentVersion::class, 'ro_training_version_assessments', 'course_version_id', 'assessment_version_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('ro_assessment_versions.id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isDraft(): bool
    {
        return $this->status === TrainingContentStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === TrainingContentStatus::Published;
    }

    public function label(): string
    {
        return 'v'.$this->version_number;
    }

    /**
     * Stated estimate, else the sum of the lessons' durations.
     */
    public function estimatedMinutes(): ?int
    {
        if ($this->estimated_minutes !== null) {
            return $this->estimated_minutes;
        }

        $sum = (int) $this->lessons->sum('duration_minutes');

        return $sum > 0 ? $sum : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-training')
            ->logOnly(['course_id', 'version_number', 'status', 'published_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
