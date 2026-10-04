<?php

namespace App\Modules\RecruiterOperations\Models;

use Database\Factories\RecruiterOperations\TrainingTrackFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A recruiter training track such as "OPT Recruiter" or "Bench Sales
 * Recruiter". The top of the training hierarchy: track, course, module,
 * lesson. Courses of one track are never shown or assigned under another.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $status
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, TrainingCourse> $courses
 */
class TrainingTrack extends Model
{
    /** @use HasFactory<TrainingTrackFactory> */
    use HasFactory;

    public const OPT_RECRUITER = 'opt-recruiter';

    public const BENCH_SALES_RECRUITER = 'bench-sales-recruiter';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * Route value for courses that are not in any track yet.
     */
    public const UNASSIGNED = 'unassigned';

    protected $table = 'ro_training_tracks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /**
     * @return HasMany<TrainingCourse, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(TrainingCourse::class, 'training_track_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }
}
