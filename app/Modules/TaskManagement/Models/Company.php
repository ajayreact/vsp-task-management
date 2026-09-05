<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\TaskManagement\Enums\CompanyStatus;
use Database\Factories\TaskManagement\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A work client. Delivery relationships live on `tm_companies`.
 *
 * @property int $id
 * @property string $name
 * @property string|null $website
 * @property string $code
 * @property CompanyStatus $status
 * @property string|null $primary_contact_name
 * @property string|null $primary_contact_email
 * @property string|null $primary_contact_phone
 * @property string|null $notes
 * @property int|null $monthly_post_target
 * @property bool $holiday_india_enabled
 * @property bool $holiday_usa_enabled
 * @property-read int|null $projects_count
 */
class Company extends Model implements HasMedia
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $table = 'tm_companies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'website',
        'code',
        'status',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'notes',
        'monthly_post_target',
        'holiday_india_enabled',
        'holiday_usa_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'monthly_post_target' => 'integer',
            'holiday_india_enabled' => 'boolean',
            'holiday_usa_enabled' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'tm_company_id');
    }

    /**
     * @return HasOne<CompanyShareLink, $this>
     */
    public function shareLink(): HasOne
    {
        return $this->hasOne(CompanyShareLink::class, 'tm_company_id');
    }

    /**
     * @return HasMany<CompanyDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class, 'tm_company_id');
    }

    /**
     * @return HasMany<CompanyPhoneNumber, $this>
     */
    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(CompanyPhoneNumber::class, 'tm_company_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<ContentCalendarItem, $this>
     */
    public function contentCalendarItems(): HasMany
    {
        return $this->hasMany(ContentCalendarItem::class, 'tm_company_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logos');
        $this->addMediaCollection('brand_assets');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task-management')
            ->logOnly(['name', 'code', 'status', 'monthly_post_target'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if ($company->monthly_post_target === null) {
                $company->monthly_post_target = 18;
            }
        });
    }
}
