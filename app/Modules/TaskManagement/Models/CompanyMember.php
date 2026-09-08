<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Supporting employee on a work client (primary lives on tm_companies).
 *
 * @property int $id
 * @property int $tm_company_id
 * @property int $employee_id
 * @property-read Company $company
 * @property-read Employee $employee
 */
class CompanyMember extends Model
{
    protected $table = 'tm_company_members';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_company_id',
        'employee_id',
    ];

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tm_company_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
