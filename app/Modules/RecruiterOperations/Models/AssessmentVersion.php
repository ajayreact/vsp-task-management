<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentStatus;
use Database\Factories\RecruiterOperations\AssessmentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One numbered edition of an assessment: its questions and settings. Only a
 * draft may change. Once published it is frozen; assignments and attempts
 * stay pinned to it, so results always show the questions actually asked.
 *
 * @property int $id
 * @property int $assessment_id
 * @property int $version_number
 * @property AssessmentStatus $status
 * @property string|null $instructions
 * @property int $passing_percentage
 * @property int|null $time_limit_minutes
 * @property int $max_attempts
 * @property bool $randomize_questions
 * @property bool $randomize_options
 * @property bool $show_result
 * @property bool $allow_review
 * @property Carbon|null $published_at
 * @property int|null $created_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Assessment $assessment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssessmentQuestion> $questions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssessmentAssignment> $assignments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TrainingCourseVersion> $trainingVersions
 * @property-read User|null $creator
 */
class AssessmentVersion extends Model
{
    /** @use HasFactory<AssessmentVersionFactory> */
    use HasFactory, LogsActivity;

    public const DEFAULT_PASSING_PERCENTAGE = 70;

    public const DEFAULT_MAX_ATTEMPTS = 2;

    protected $table = 'ro_assessment_versions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'instructions',
        'passing_percentage',
        'time_limit_minutes',
        'max_attempts',
        'randomize_questions',
        'randomize_options',
        'show_result',
        'allow_review',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'passing_percentage' => self::DEFAULT_PASSING_PERCENTAGE,
        'max_attempts' => self::DEFAULT_MAX_ATTEMPTS,
        'randomize_questions' => false,
        'randomize_options' => false,
        'show_result' => true,
        'allow_review' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'status' => AssessmentStatus::class,
            'passing_percentage' => 'integer',
            'time_limit_minutes' => 'integer',
            'max_attempts' => 'integer',
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
            'show_result' => 'boolean',
            'allow_review' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    /**
     * @return HasMany<AssessmentQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class, 'assessment_version_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<AssessmentAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssessmentAssignment::class, 'assessment_version_id');
    }

    /**
     * @return BelongsToMany<TrainingCourseVersion, $this>
     */
    public function trainingVersions(): BelongsToMany
    {
        return $this->belongsToMany(TrainingCourseVersion::class, 'ro_training_version_assessments', 'assessment_version_id', 'course_version_id')
            ->withTimestamps();
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
        return $this->status === AssessmentStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === AssessmentStatus::Published;
    }

    public function label(): string
    {
        return 'v'.$this->version_number;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-assessments')
            ->logOnly(['assessment_id', 'version_number', 'status', 'published_at', 'passing_percentage', 'time_limit_minutes', 'max_attempts'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
