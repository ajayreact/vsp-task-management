<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ReviewDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_deliverable_id
 * @property int $reviewer_user_id
 * @property int $round
 * @property ReviewDecision $decision
 * @property string|null $comments
 * @property Carbon $reviewed_at
 * @property-read Deliverable $deliverable
 * @property-read User $reviewer
 */
class DeliverableReview extends Model
{
    protected $table = 'tm_deliverable_reviews';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_deliverable_id',
        'reviewer_user_id',
        'round',
        'decision',
        'comments',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => ReviewDecision::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Deliverable, $this>
     */
    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class, 'tm_deliverable_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    /**
     * @return HasMany<ReviewAnnotation, $this>
     */
    public function annotations(): HasMany
    {
        return $this->hasMany(ReviewAnnotation::class, 'tm_deliverable_review_id');
    }
}
