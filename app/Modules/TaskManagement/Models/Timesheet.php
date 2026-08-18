<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use Database\Factories\TaskManagement\TimesheetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property string $total_hours
 * @property TimesheetStatus $status
 * @property Carbon|null $submitted_at
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 * @property string|null $review_note
 * @property-read Employee $employee
 * @property-read User|null $approver
 */
class Timesheet extends Model
{
    /** @use HasFactory<TimesheetFactory> */
    use HasFactory;

    protected $table = 'tm_timesheets';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'total_hours',
        'status',
        'submitted_at',
        'approved_by_user_id',
        'approved_at',
        'review_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => TimesheetStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'tm_timesheet_id');
    }

    public function refreshTotal(): void
    {
        $seconds = (int) $this->entries()->where('is_running', false)->sum('duration_seconds');

        $this->forceFill([
            'total_hours' => round($seconds / 3600, 2),
        ])->save();
    }
}
