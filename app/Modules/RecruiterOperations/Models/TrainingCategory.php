<?php

namespace App\Modules\RecruiterOperations\Models;

use App\Modules\Core\Models\User;
use Database\Factories\RecruiterOperations\TrainingCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A training level such as "Level 1 - U.S. Fundamentals". Deactivated rather
 * than deleted once it holds courses.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $level_number
 * @property int $sort_order
 * @property bool $is_active
 * @property int|null $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TrainingCourse> $courses
 * @property-read User|null $creator
 */
class TrainingCategory extends Model
{
    /** @use HasFactory<TrainingCategoryFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ro_training_categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'level_number',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level_number' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TrainingCourse, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(TrainingCourse::class, 'category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderByRaw('level_number is null')->orderBy('level_number')->orderBy('name');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('recruiter-training')
            ->logOnly(['name', 'level_number', 'sort_order', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
