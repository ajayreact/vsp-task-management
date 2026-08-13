<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The join between a project and an employee. A model rather than a bare pivot
 * so that reading the team is typed the same way as everything else.
 *
 * @property int $id
 * @property int $tm_project_id
 * @property int $employee_id
 * @property ProjectRole $project_role
 * @property-read Employee $employee
 */
class ProjectMember extends Model
{
    protected $table = 'tm_project_members';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_project_id',
        'employee_id',
        'project_role',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'project_role' => ProjectRole::class,
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
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'tm_project_id');
    }
}
