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
 * @property string|null $short_code
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
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
        'short_code',
        'expires_at',
        'revoked_at',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function publicUrl(): string
    {
        if ($this->short_code !== null) {
            return route('share.short.show', ['shortCode' => $this->short_code]);
        }

        return route('share.show', ['token' => $this->token]);
    }

    public function publicFileUrl(string $mediaUuid): string
    {
        if ($this->short_code !== null) {
            return route('share.short.file', [
                'shortCode' => $this->short_code,
                'mediaUuid' => $mediaUuid,
            ]);
        }

        return route('share.file', [
            'token' => $this->token,
            'mediaUuid' => $mediaUuid,
        ]);
    }

    public function publicApproveUrl(): string
    {
        if ($this->short_code !== null) {
            return route('share.short.approve', ['shortCode' => $this->short_code]);
        }

        return route('share.approve', ['token' => $this->token]);
    }

    public function publicRequestChangesUrl(): string
    {
        if ($this->short_code !== null) {
            return route('share.short.request-changes', ['shortCode' => $this->short_code]);
        }

        return route('share.request-changes', ['token' => $this->token]);
    }
}
