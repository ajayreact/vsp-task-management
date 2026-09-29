<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use Database\Factories\RecruiterOperations\TrainingCourseFactory;
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
 * A training course. Its content lives in numbered versions; the course only
 * carries the title, category and which published version new assignments get.
 *
 * Status and current_version_id are written by TrainingContentService.
 *
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property TrainingContentStatus $status
 * @property int|null $current_version_id
 * @property int|null $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read TrainingCategory $category
 * @property-read TrainingCourseVersion|null $currentVersion
 * @property-read TrainingCourseVersion|null $draftVersion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TrainingCourseVersion> $versions
 */
class TrainingCourse extends Model
{
    /** @use HasFactory<TrainingCourseFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ro_training_courses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TrainingContentStatus::class,
            'current_version_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TrainingCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'category_id');
    }

    /**
     * @return HasMany<TrainingCourseVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(TrainingCourseVersion::class, 'course_id');
    }

    /**
     * @return BelongsTo<TrainingCourseVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(TrainingCourseVersion::class, 'current_version_id');
    }

    /**
     * A course has at most one draft version at a time.
     *
     * @return HasOne<TrainingCourseVersion, $this>
     */
    public function draftVersion(): HasOne
    {
        return $this->hasOne(TrainingCourseVersion::class, 'course_id')
            ->where('status', TrainingContentStatus::Draft->value);
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
        return $this->status === TrainingContentStatus::Archived;
    }

    /**
     * Whether new assignments can be made: live, with a published version.
     */
    public function isAssignable(): bool
    {
        return $this->status === TrainingContentStatus::Published && $this->current_version_id !== null;
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAssignable(Builder $query): void
    {
        $query->where('status', TrainingContentStatus::Published->value)->whereNotNull('current_version_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-training')
            ->logOnly(['category_id', 'title', 'status', 'current_version_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
