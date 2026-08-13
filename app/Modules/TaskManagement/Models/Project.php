<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\ProjectStatus;
use Database\Factories\TaskManagement\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $tm_company_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property ProjectStatus $status
 * @property Carbon|null $start_date
 * @property Carbon|null $due_date
 * @property int|null $manager_employee_id
 * @property string|null $budget_hours
 * @property-read Company $company
 * @property-read Employee|null $manager
 * @property-read int|null $tasks_count
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'tm_projects';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_company_id',
        'name',
        'code',
        'description',
        'status',
        'start_date',
        'due_date',
        'manager_employee_id',
        'budget_hours',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

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
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    /**
     * The team as a set of employees, for writing: `sync()` handles adds,
     * removals and role changes in one call.
     *
     * @return BelongsToMany<Employee, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'tm_project_members', 'tm_project_id', 'employee_id')
            ->withPivot('project_role')
            ->withTimestamps();
    }

    /**
     * The same team as join rows, for reading: the project role is a typed
     * attribute here rather than an untyped pivot bag.
     *
     * @return HasMany<ProjectMember, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'tm_project_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'tm_project_id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAcceptingWork(Builder $query): void
    {
        $query->whereIn('status', [ProjectStatus::Planning, ProjectStatus::Active]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task-management')
            ->logOnly(['name', 'code', 'status', 'manager_employee_id', 'due_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
