<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\AvailabilityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property Carbon $date
 * @property AvailabilityStatus $status
 * @property string|null $capacity_hours
 * @property string|null $notes
 * @property-read Employee $employee
 */
class EmployeeAvailability extends Model
{
    protected $table = 'tm_employee_availability';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'capacity_hours',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AvailabilityStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
