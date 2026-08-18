<?php

namespace App\Modules\TaskManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_deliverable_review_id
 * @property int|null $media_id
 * @property int|null $page
 * @property string|null $x
 * @property string|null $y
 * @property string $body
 * @property Carbon|null $resolved_at
 */
class ReviewAnnotation extends Model
{
    protected $table = 'tm_review_annotations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_deliverable_review_id',
        'media_id',
        'page',
        'x',
        'y',
        'body',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DeliverableReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(DeliverableReview::class, 'tm_deliverable_review_id');
    }
}
