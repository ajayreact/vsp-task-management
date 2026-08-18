<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $source_tm_task_id
 * @property int $created_by_user_id
 * @property RecurrenceFrequency $frequency
 * @property int $interval
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property int|null $max_occurrences
 * @property int $occurrences_generated
 * @property bool $is_active
 * @property Carbon|null $last_generated_at
 * @property-read Task $sourceTask
 * @property-read User $creator
 */
class TaskRecurrenceRule extends Model
{
    protected $table = 'tm_task_recurrence_rules';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_tm_task_id',
        'created_by_user_id',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'max_occurrences',
        'occurrences_generated',
        'is_active',
        'last_generated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => RecurrenceFrequency::class,
            'interval' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'max_occurrences' => 'integer',
            'occurrences_generated' => 'integer',
            'is_active' => 'boolean',
            'last_generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function sourceTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'source_tm_task_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'tm_recurrence_rule_id');
    }
}
