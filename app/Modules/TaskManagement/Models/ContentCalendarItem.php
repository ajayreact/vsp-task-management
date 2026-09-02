<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use Database\Factories\TaskManagement\ContentCalendarItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property ContentCalendarType $content_type
 * @property ContentCalendarPlatform $platform
 * @property string|null $description
 * @property ContentCalendarStatus $status
 * @property string|null $internal_notes
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
        'content_type',
        'platform',
        'description',
        'status',
        'internal_notes',
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
            'content_type' => ContentCalendarType::class,
            'platform' => ContentCalendarPlatform::class,
            'status' => ContentCalendarStatus::class,
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task-management')
            ->logOnly(['scheduled_date', 'scheduled_time', 'content_type', 'platform', 'description', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
