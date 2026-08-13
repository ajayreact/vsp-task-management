<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_task_id
 * @property TaskStatus|null $from_status
 * @property TaskStatus $to_status
 * @property int|null $changed_by_user_id
 * @property Carbon $changed_at
 * @property-read User|null $changedBy
 */
class TaskStatusChange extends Model
{
    protected $table = 'tm_task_status_history';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_task_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => TaskStatus::class,
            'to_status' => TaskStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
