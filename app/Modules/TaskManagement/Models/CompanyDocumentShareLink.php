<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tm_company_document_id
 * @property string $token
 * @property string|null $short_code
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property int $created_by_user_id
 * @property-read CompanyDocument $document
 * @property-read User $createdBy
 */
class CompanyDocumentShareLink extends Model
{
    protected $table = 'tm_company_document_share_links';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_company_document_id',
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
     * @return BelongsTo<CompanyDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(CompanyDocument::class, 'tm_company_document_id');
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
            return route('document-share.short.show', ['shortCode' => $this->short_code]);
        }

        return route('document-share.show', ['token' => $this->token]);
    }

    public function publicFileUrl(string $mediaUuid): string
    {
        if ($this->short_code !== null) {
            return route('document-share.short.file', [
                'shortCode' => $this->short_code,
                'mediaUuid' => $mediaUuid,
            ]);
        }

        return route('document-share.file', [
            'token' => $this->token,
            'mediaUuid' => $mediaUuid,
        ]);
    }

    public function publicFileDownloadUrl(string $mediaUuid): string
    {
        if ($this->short_code !== null) {
            return route('document-share.short.file.download', [
                'shortCode' => $this->short_code,
                'mediaUuid' => $mediaUuid,
            ]);
        }

        return route('document-share.file.download', [
            'token' => $this->token,
            'mediaUuid' => $mediaUuid,
        ]);
    }
}
