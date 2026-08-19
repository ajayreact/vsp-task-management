<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeOfficeAssignment extends Model
{
    protected $table = 'att_employee_office_assignments';

    protected $fillable = [
        'employee_id',
        'office_location_id',
    ];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<OfficeLocation, $this>
     */
    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class);
    }
}
