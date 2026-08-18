<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use Database\Factories\TaskManagement\EmployeeCapacityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Planned weekly hours. Several rows may exist for one person; the current
 * one is the latest `effective_from` that is not in the future.
 *
 * @property int $id
 * @property int $employee_id
 * @property string $weekly_hours
 * @property list<int> $working_days
 * @property Carbon $effective_from
 * @property-read Employee $employee
 */
class EmployeeCapacity extends Model
{
    /** @use HasFactory<EmployeeCapacityFactory> */
    use HasFactory;

    protected $table = 'tm_employee_capacity';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'weekly_hours',
        'working_days',
        'effective_from',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'working_days' => 'array',
            'effective_from' => 'date',
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
     * Hours this person is expected to work on one working day.
     */
    public function dailyHours(): float
    {
        $days = count($this->working_days);

        return $days === 0 ? 0.0 : round((float) $this->weekly_hours / $days, 2);
    }

    public function worksOn(Carbon $date): bool
    {
        return in_array((int) $date->isoWeekday(), $this->working_days, true);
    }

    /**
     * @return list<int>
     */
    public static function defaultWorkingDays(): array
    {
        return [1, 2, 3, 4, 5];
    }
}
