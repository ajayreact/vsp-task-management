<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentType;
use Database\Factories\RecruiterOperations\AssessmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A quiz (and, later, other kinds of assessment) with numbered versions.
 * Questions, settings and assignments all hang off a version.
 *
 * @property int $id
 * @property AssessmentType $type
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property AssessmentStatus $status
 * @property int|null $current_version_id
 * @property int|null $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssessmentVersion> $versions
 * @property-read AssessmentVersion|null $currentVersion
 * @property-read AssessmentVersion|null $draftVersion
 * @property-read User|null $creator
 */
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ro_assessments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssessmentType::class,
            'status' => AssessmentStatus::class,
            'current_version_id' => 'integer',
        ];
    }

    /**
     * @return HasMany<AssessmentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AssessmentVersion::class, 'assessment_id');
    }

    /**
     * @return BelongsTo<AssessmentVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'current_version_id');
    }

    /**
     * @return HasOne<AssessmentVersion, $this>
     */
    public function draftVersion(): HasOne
    {
        return $this->hasOne(AssessmentVersion::class, 'assessment_id')->where('status', AssessmentStatus::Draft->value);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isArchived(): bool
    {
        return $this->status === AssessmentStatus::Archived;
    }

    public function isAssignable(): bool
    {
        return $this->status === AssessmentStatus::Published && $this->current_version_id !== null && $this->type->isAvailable();
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAssignable(Builder $query): void
    {
        $query->where('status', AssessmentStatus::Published->value)
            ->whereNotNull('current_version_id')
            ->whereIn('type', array_map(fn (AssessmentType $type) => $type->value, AssessmentType::available()));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-assessments')
            ->logOnly(['type', 'title', 'status', 'current_version_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
