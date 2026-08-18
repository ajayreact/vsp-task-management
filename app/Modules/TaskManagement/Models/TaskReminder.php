<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_task_id
 * @property int $recipient_user_id
 * @property int $created_by_user_id
 * @property Carbon $remind_at
 * @property string|null $message
 * @property Carbon|null $sent_at
 * @property-read Task $task
 * @property-read User $recipient
 * @property-read User $creator
 */
class TaskReminder extends Model
{
    protected $table = 'tm_task_reminders';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_task_id',
        'recipient_user_id',
        'created_by_user_id',
        'remind_at',
        'message',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tm_task_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->sent_at === null;
    }
}
