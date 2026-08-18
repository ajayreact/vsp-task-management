<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Opaque public token that resolves to one deliverable. The token is the only
 * identifier exposed outside the staff app.
 *
 * @property int $id
 * @property int $tm_deliverable_id
 * @property string $token
 * @property int $created_by_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Deliverable $deliverable
 * @property-read User $createdBy
 */
class DeliverableShareLink extends Model
{
    protected $table = 'tm_deliverable_share_links';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_deliverable_id',
        'token',
        'created_by_user_id',
    ];

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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function publicUrl(): string
    {
        return route('share.show', ['token' => $this->token]);
    }

    public function publicFileUrl(string $mediaUuid): string
    {
        return route('share.file', [
            'token' => $this->token,
            'mediaUuid' => $mediaUuid,
        ]);
    }
}
