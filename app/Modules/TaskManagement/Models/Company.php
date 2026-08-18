<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\TaskManagement\Enums\CompanyStatus;
use Database\Factories\TaskManagement\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A work client. Delivery relationships live on `tm_companies`.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property CompanyStatus $status
 * @property string|null $primary_contact_name
 * @property string|null $primary_contact_email
 * @property string|null $primary_contact_phone
 * @property string|null $notes
 * @property-read int|null $projects_count
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'tm_companies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'status',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
        ];
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'tm_company_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task-management')
            ->logOnly(['name', 'code', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
