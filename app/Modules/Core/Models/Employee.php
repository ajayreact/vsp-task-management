<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Enums\EmployeeStatus;
use Database\Factories\Core\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The internal person behind a login. Both modules assign work to employees,
 * so this is deliberately part of the shared kernel.
 */
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $department_id
 * @property string $employee_code
 * @property string|null $designation
 * @property int|null $reporting_to_id
 * @property string|null $phone
 * @property Carbon|null $joined_on
 * @property Carbon|null $exited_on
 * @property EmployeeStatus $status
 * @property-read User $user
 * @property-read Department|null $department
 * @property-read Employee|null $manager
 */
class Employee extends Model implements HasMedia
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'department_id',
        'employee_code',
        'designation',
        'reporting_to_id',
        'phone',
        'joined_on',
        'exited_on',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_on' => 'date',
            'exited_on' => 'date',
            'status' => EmployeeStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_to_id');
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_to_id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAssignable(Builder $query): void
    {
        $query->where('status', EmployeeStatus::Active);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->width(96)
            ->height(96);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['department_id', 'designation', 'reporting_to_id', 'status', 'exited_on'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
