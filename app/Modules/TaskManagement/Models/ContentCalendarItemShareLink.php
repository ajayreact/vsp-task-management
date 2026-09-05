<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Support\CompanySlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_content_calendar_item_id
 * @property string $token
 * @property string|null $short_code
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property int $created_by_user_id
 * @property-read ContentCalendarItem $item
 * @property-read User $createdBy
 */
class ContentCalendarItemShareLink extends Model
{
    protected $table = 'tm_content_calendar_item_share_links';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_content_calendar_item_id',
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
     * @return BelongsTo<ContentCalendarItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ContentCalendarItem::class, 'tm_content_calendar_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function companySlug(): string
    {
        $this->loadMissing('item.company');

        return CompanySlug::fromName($this->item?->company?->name);
    }

    public function publicUrl(): string
    {
        if ($this->short_code !== null) {
            return route('share.client.show', [
                'companySlug' => $this->companySlug(),
                'shortCode' => $this->short_code,
            ]);
        }

        return route('content-share.show', ['token' => $this->token]);
    }

    public function legacyShortUrl(): string
    {
        if ($this->short_code === null) {
            return $this->publicUrl();
        }

        return route('content-share.short.show', ['shortCode' => $this->short_code]);
    }

    public function publicFileUrl(string $mediaUuid): string
    {
        if ($this->short_code !== null) {
            return route('share.client.file', [
                'companySlug' => $this->companySlug(),
                'shortCode' => $this->short_code,
                'mediaUuid' => $mediaUuid,
            ]);
        }

        return route('content-share.file', [
            'token' => $this->token,
            'mediaUuid' => $mediaUuid,
        ]);
    }

    public function publicFileDownloadUrl(string $mediaUuid): string
    {
        if ($this->short_code !== null) {
            return route('share.client.file.download', [
                'companySlug' => $this->companySlug(),
                'shortCode' => $this->short_code,
                'mediaUuid' => $mediaUuid,
            ]);
        }

        return route('content-share.file.download', [
            'token' => $this->token,
            'mediaUuid' => $mediaUuid,
        ]);
    }

    public function approveUrl(bool $preferLegacy = false): string
    {
        if ($preferLegacy || $this->short_code === null) {
            return route('content-share.approve', ['token' => $this->token]);
        }

        return route('share.client.approve', [
            'companySlug' => $this->companySlug(),
            'shortCode' => $this->short_code,
        ]);
    }

    public function requestChangesUrl(bool $preferLegacy = false): string
    {
        if ($preferLegacy || $this->short_code === null) {
            return route('content-share.request-changes', ['token' => $this->token]);
        }

        return route('share.client.request-changes', [
            'companySlug' => $this->companySlug(),
            'shortCode' => $this->short_code,
        ]);
    }
}
