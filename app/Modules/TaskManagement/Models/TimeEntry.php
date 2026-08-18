<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\TimeSource;
use Database\Factories\TaskManagement\TimeEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_task_id
 * @property int $employee_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property int $duration_seconds
 * @property bool $is_running
 * @property TimeSource $source
 * @property string|null $note
 * @property bool $is_billable
 * @property int|null $tm_timesheet_id
 * @property int|null $running_for_employee_id
 * @property-read Task $task
 * @property-read Employee $employee
 * @property-read Timesheet|null $timesheet
 */
class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    protected $table = 'tm_time_entries';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_task_id',
        'employee_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'is_running',
        'source',
        'note',
        'is_billable',
        'tm_timesheet_id',
        'running_for_employee_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_running' => 'boolean',
            'source' => TimeSource::class,
            'is_billable' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TimeEntry $entry): void {
            $entry->running_for_employee_id = $entry->is_running ? $entry->employee_id : null;
        });
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tm_task_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Timesheet, $this>
     */
    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class, 'tm_timesheet_id');
    }

    public function hours(): float
    {
        return round($this->duration_seconds / 3600, 2);
    }
}
