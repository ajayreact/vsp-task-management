<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_task_id
 * @property string $title
 * @property bool $is_completed
 * @property int|null $completed_by_user_id
 * @property Carbon|null $completed_at
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Task $task
 * @property-read User|null $completedBy
 */
class TaskChecklistItem extends Model
{
    protected $table = 'tm_task_checklist_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_task_id',
        'title',
        'is_completed',
        'completed_by_user_id',
        'completed_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
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
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
