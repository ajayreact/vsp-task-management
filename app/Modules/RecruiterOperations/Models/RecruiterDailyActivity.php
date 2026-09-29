<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterActivityType;
use Carbon\CarbonInterface;
use Database\Factories\RecruiterOperations\RecruiterDailyActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * What a recruiter actually did on a day, logged by hand. Separate from
 * recruiter tasks (what was assigned) and from Attendance. Never holds or
 * links to candidate records; quantity is only a number the recruiter reports.
 *
 * Owner, actor columns, duration and the task link are written by
 * RecruiterDailyActivityService.
 *
 * @property int $id
 * @property int $employee_id
 * @property Carbon $activity_date
 * @property RecruiterActivityType $activity_type
 * @property string $title
 * @property string|null $description
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int|null $duration_minutes
 * @property int|null $recruiter_task_id
 * @property int|null $quantity
 * @property string|null $remarks
 * @property int|null $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Employee $employee
 * @property-read RecruiterTask|null $task
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class RecruiterDailyActivity extends Model
{
    /** @use HasFactory<RecruiterDailyActivityFactory> */
    use HasFactory, LogsActivity;

    /**
     * How many calendar days back a recruiter may record or change their own
     * activities. Older records need a lead's correction.
     */
    public const SELF_SERVICE_DAYS = 7;

    protected $table = 'ro_daily_activities';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'activity_date',
        'activity_type',
        'title',
        'description',
        'start_time',
        'end_time',
        'quantity',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'activity_type' => RecruiterActivityType::class,
            'duration_minutes' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<RecruiterTask, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(RecruiterTask::class, 'recruiter_task_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public static function earliestSelfServiceDate(): Carbon
    {
        return today()->subDays(self::SELF_SERVICE_DAYS);
    }

    public function isWithinSelfServiceWindow(): bool
    {
        return $this->activity_date->greaterThanOrEqualTo(self::earliestSelfServiceDate());
    }

    public function isOwnedBy(?Employee $employee): bool
    {
        return $employee !== null && $this->employee_id === $employee->id;
    }

    /**
     * "09:30" from the stored "09:30:00".
     */
    public static function clock(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForEmployee(Builder $query, Employee|int $employee): void
    {
        $query->where('employee_id', $employee instanceof Employee ? $employee->id : $employee);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForDate(Builder $query, CarbonInterface|string $date): void
    {
        $query->whereDate('activity_date', $date instanceof CarbonInterface ? $date->toDateString() : $date);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeBetweenDates(Builder $query, CarbonInterface|string $from, CarbonInterface|string $to): void
    {
        $query->whereBetween('activity_date', [
            $from instanceof CarbonInterface ? $from->toDateString() : $from,
            $to instanceof CarbonInterface ? $to->toDateString() : $to,
        ]);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForType(Builder $query, RecruiterActivityType|string $type): void
    {
        $query->where('activity_type', $type instanceof RecruiterActivityType ? $type->value : $type);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForTask(Builder $query, RecruiterTask|int $task): void
    {
        $query->where('recruiter_task_id', $task instanceof RecruiterTask ? $task->id : $task);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['activity_date', 'activity_type', 'title', 'duration_minutes', 'quantity', 'recruiter_task_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
