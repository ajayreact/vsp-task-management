<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_content_calendar_item_id
 * @property string|null $from_status
 * @property string $to_status
 * @property string|null $note
 * @property int|null $created_by_user_id
 * @property Carbon $created_at
 */
class ContentCalendarStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'tm_content_calendar_status_histories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_content_calendar_item_id',
        'from_status',
        'to_status',
        'note',
        'created_by_user_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'from_status' => ContentCalendarStatus::class,
            'to_status' => ContentCalendarStatus::class,
        ];
    }

    /**
     * @return BelongsTo<ContentCalendarItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ContentCalendarItem::class, 'tm_content_calendar_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
