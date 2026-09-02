<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\PersonalTodoStatus;
use App\Modules\TaskManagement\Enums\TaskPriority;
use Database\Factories\TaskManagement\PersonalTodoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lightweight personal reminder for an employee. Not a formal task.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $note
 * @property Carbon|null $due_date
 * @property string|null $due_time
 * @property TaskPriority $priority
 * @property PersonalTodoStatus $status
 * @property Carbon|null $completed_at
 * @property int|null $sort_order
 * @property Carbon|null $reminder_at
 * @property Carbon|null $reminder_sent_at
 * @property int|null $tm_task_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Task|null $task
 */
class PersonalTodo extends Model
{
    /** @use HasFactory<PersonalTodoFactory> */
    use HasFactory;

    protected $table = 'tm_personal_todos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'note',
        'due_date',
        'due_time',
        'priority',
        'status',
        'completed_at',
        'sort_order',
        'reminder_at',
        'reminder_sent_at',
        'tm_task_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'priority' => TaskPriority::class,
            'status' => PersonalTodoStatus::class,
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
            'reminder_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tm_task_id');
    }

    public function effectiveDueAt(): ?Carbon
    {
        if ($this->due_date === null) {
            return null;
        }

        $due = $this->due_date->copy()->startOfDay();

        if ($this->due_time !== null) {
            $parts = explode(':', (string) $this->due_time);

            return $due->setTime((int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0));
        }

        return $due->endOfDay();
    }

    public function isOverdue(): bool
    {
        if ($this->status->isCompleted()) {
            return false;
        }

        $due = $this->effectiveDueAt();

        return $due !== null && $due->isPast();
    }

    public function isDueToday(): bool
    {
        return $this->due_date !== null && $this->due_date->isToday();
    }

    public function isUpcoming(int $days = 7): bool
    {
        if ($this->status->isCompleted() || $this->due_date === null) {
            return false;
        }

        $start = today()->addDay();
        $end = today()->addDays($days);

        return $this->due_date->betweenIncluded($start, $end);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', PersonalTodoStatus::Pending);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeCompletedToday(Builder $query): void
    {
        $query->where('status', PersonalTodoStatus::Completed)
            ->whereDate('completed_at', today());
    }
}
