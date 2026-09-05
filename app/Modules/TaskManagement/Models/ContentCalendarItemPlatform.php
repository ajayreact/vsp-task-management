<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tm_content_calendar_item_id
 * @property ContentCalendarPlatform $platform
 */
class ContentCalendarItemPlatform extends Model
{
    protected $table = 'tm_content_calendar_item_platforms';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_content_calendar_item_id',
        'platform',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => ContentCalendarPlatform::class,
        ];
    }

    /**
     * @return BelongsTo<ContentCalendarItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ContentCalendarItem::class, 'tm_content_calendar_item_id');
    }
}
