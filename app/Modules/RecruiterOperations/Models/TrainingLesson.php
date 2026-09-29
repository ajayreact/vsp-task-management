<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingLessonContentType;
use Database\Factories\RecruiterOperations\TrainingLessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One lesson inside a course version. Editable only while its version is a
 * draft. The body is plain text: it is shown as text and read aloud, never
 * rendered as HTML.
 *
 * The optional file (video, PDF or image) is kept on the private disk and only
 * served through an authorized route.
 *
 * @property int $id
 * @property int $course_version_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property TrainingLessonContentType $content_type
 * @property string|null $body
 * @property int|null $duration_minutes
 * @property bool $is_required
 * @property string|null $external_url
 * @property int|null $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read TrainingCourseVersion $version
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TrainingLessonCompletion> $completions
 */
class TrainingLesson extends Model implements HasMedia
{
    /** @use HasFactory<TrainingLessonFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    public const FILE_COLLECTION = 'lesson_file';

    protected $table = 'ro_training_lessons';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'content_type',
        'body',
        'duration_minutes',
        'is_required',
        'external_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'content_type' => TrainingLessonContentType::class,
            'duration_minutes' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FILE_COLLECTION)
            ->singleFile()
            ->useDisk((string) config('recruiter-training.media.disk', 'local'));
    }

    /**
     * @return BelongsTo<TrainingCourseVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(TrainingCourseVersion::class, 'course_version_id');
    }

    /**
     * @return HasMany<TrainingLessonCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TrainingLessonCompletion::class, 'lesson_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia(self::FILE_COLLECTION);
    }

    public function isEditable(): bool
    {
        return $this->version->isDraft();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-training')
            ->logOnly(['course_version_id', 'title', 'sort_order', 'content_type', 'is_required', 'external_url'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
