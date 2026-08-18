<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property Carbon $date
 * @property string $assigned_hours
 * @property string $available_hours
 * @property string $utilisation_pct
 */
class WorkloadSnapshot extends Model
{
    protected $table = 'tm_workload_snapshots';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'date',
        'assigned_hours',
        'available_hours',
        'utilisation_pct',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
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
