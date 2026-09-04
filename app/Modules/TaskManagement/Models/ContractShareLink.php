<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractShareLink extends Model
{
    protected $table = 'tm_contract_share_links';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_contract_id',
        'token',
        'short_code',
        'expiry_preset',
        'expires_at',
        'revoked_at',
        'viewed_at',
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
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'tm_contract_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function publicUrl(): string
    {
        if ($this->short_code !== null) {
            return route('contract-share.short.show', ['shortCode' => $this->short_code]);
        }

        return route('contract-share.show', ['token' => $this->token]);
    }

    public function publicSignUrl(): string
    {
        if ($this->short_code !== null) {
            return route('contract-share.short.sign', ['shortCode' => $this->short_code]);
        }

        return route('contract-share.sign', ['token' => $this->token]);
    }

    public function publicPdfUrl(): string
    {
        if ($this->short_code !== null) {
            return route('contract-share.short.pdf', ['shortCode' => $this->short_code]);
        }

        return route('contract-share.pdf', ['token' => $this->token]);
    }

    public function resolveExpiry(string $preset): ?\Illuminate\Support\Carbon
    {
        return match ($preset) {
            '7_days' => now()->addDays(7),
            '15_days' => now()->addDays(15),
            '30_days' => now()->addDays(30),
            default => null,
        };
    }
}
