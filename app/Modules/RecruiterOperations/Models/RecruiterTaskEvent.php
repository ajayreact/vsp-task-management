<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One entry on a recruiter task's permanent timeline.
 *
 * @property int $id
 * @property int $ro_task_id
 * @property RecruiterTaskEventType $event
 * @property RecruiterTaskStatus|null $from_status
 * @property RecruiterTaskStatus|null $to_status
 * @property int|null $from_employee_id
 * @property int|null $to_employee_id
 * @property int|null $actor_user_id
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 * @property-read RecruiterTask $task
 * @property-read Employee|null $fromEmployee
 * @property-read Employee|null $toEmployee
 * @property-read User|null $actor
 */
class RecruiterTaskEvent extends Model
{
    protected $table = 'ro_task_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ro_task_id',
        'event',
        'from_status',
        'to_status',
        'from_employee_id',
        'to_employee_id',
        'actor_user_id',
        'reason',
        'metadata',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => RecruiterTaskEventType::class,
            'from_status' => RecruiterTaskStatus::class,
            'to_status' => RecruiterTaskStatus::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RecruiterTask, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(RecruiterTask::class, 'ro_task_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function fromEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'from_employee_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function toEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_employee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
