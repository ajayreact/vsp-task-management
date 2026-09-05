<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarTopic;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use Database\Factories\TaskManagement\ContentCalendarItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $tm_company_id
 * @property Carbon $scheduled_date
 * @property string|null $scheduled_time
 * @property int|null $post_number
 * @property ContentCalendarType $content_type
 * @property ContentCalendarTopic $topic
 * @property string|null $description
 * @property string|null $caption
 * @property string|null $hashtags
 * @property ContentCalendarStatus $status
 * @property string|null $internal_notes
 * @property string|null $client_feedback
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $published_at
 * @property string|null $published_url
 * @property int $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property-read Company $company
 * @property-read User $createdBy
 * @property-read User|null $updatedBy
 */
class ContentCalendarItem extends Model implements HasMedia
{
    /** @use HasFactory<ContentCalendarItemFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $table = 'tm_content_calendar_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_company_id',
        'scheduled_date',
        'scheduled_time',
        'post_number',
        'content_type',
        'topic',
        'description',
        'caption',
        'hashtags',
        'status',
        'internal_notes',
        'client_feedback',
        'reviewed_at',
        'published_at',
        'published_url',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'post_number' => 'integer',
            'content_type' => ContentCalendarType::class,
            'topic' => ContentCalendarTopic::class,
            'status' => ContentCalendarStatus::class,
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tm_company_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * @return HasOne<ContentCalendarItemShareLink, $this>
     */
    public function shareLink(): HasOne
    {
        return $this->hasOne(ContentCalendarItemShareLink::class, 'tm_content_calendar_item_id');
    }

    /**
     * @return HasMany<ContentCalendarStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ContentCalendarStatusHistory::class, 'tm_content_calendar_item_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * @return HasMany<ContentCalendarItemPlatform, $this>
     */
    public function platforms(): HasMany
    {
        return $this->hasMany(ContentCalendarItemPlatform::class, 'tm_content_calendar_item_id');
    }

    /**
     * @param  list<string|ContentCalendarPlatform>  $platforms
     */
    public function syncPlatforms(array $platforms): void
    {
        $normalized = collect($platforms)
            ->map(function ($platform) {
                if ($platform instanceof ContentCalendarPlatform) {
                    return $platform->value;
                }

                return ContentCalendarPlatform::tryFrom((string) $platform)?->value;
            })
            ->filter()
            ->unique()
            ->values();

        $this->platforms()->delete();

        foreach ($normalized as $platform) {
            $this->platforms()->create(['platform' => $platform]);
        }
    }

    /**
     * @return list<string>
     */
    public function platformValues(): array
    {
        return $this->platforms
            ->map(fn (ContentCalendarItemPlatform $row) => $row->platform->value)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function platformLabels(): array
    {
        return $this->platforms
            ->map(fn (ContentCalendarItemPlatform $row) => $row->platform->label())
            ->values()
            ->all();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task-management')
            ->logOnly([
                'scheduled_date',
                'scheduled_time',
                'post_number',
                'content_type',
                'topic',
                'description',
                'caption',
                'status',
                'published_url',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
