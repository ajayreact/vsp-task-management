<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Models\ContractShareLink;
use App\Modules\TaskManagement\Support\ShareShortCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ContractShareLinkService
{
    private const MAX_CREATE_ATTEMPTS = 5;

    public function getOrCreate(Contract $contract, User $user, string $expiryPreset = '30_days'): ContractShareLink
    {
        return DB::transaction(function () use ($contract, $user, $expiryPreset): ContractShareLink {
            Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();

            $existing = ContractShareLink::query()
                ->where('tm_contract_id', $contract->id)
                ->first();

            if ($existing !== null) {
                return $this->refreshExpiry($existing, $expiryPreset);
            }

            return $this->createShareLink($contract, $user, $expiryPreset);
        });
    }

    public function resolveByToken(string $token): ContractShareLink
    {
        $link = ContractShareLink::query()->where('token', $token)->first();

        return $this->resolveAccessibleLink($link, [
            'identifier_type' => 'legacy_token',
            'token_suffix' => substr($token, -8),
        ]);
    }

    public function resolveByShortCode(string $shortCode): ContractShareLink
    {
        $link = ContractShareLink::query()->where('short_code', $shortCode)->first();

        return $this->resolveAccessibleLink($link, [
            'identifier_type' => 'short_code',
            'short_code' => $shortCode,
        ]);
    }

    public function markViewed(ContractShareLink $link): void
    {
        if ($link->viewed_at === null) {
            $link->update(['viewed_at' => now()]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveAccessibleLink(?ContractShareLink $link, array $context = []): ContractShareLink
    {
        if ($link === null) {
            throw DeliverableShareException::notFound($context);
        }

        if ($link->isRevoked()) {
            throw DeliverableShareException::revoked([
                ...$context,
                'contract_id' => $link->tm_contract_id,
                'share_link_id' => $link->id,
            ]);
        }

        if ($link->isExpired()) {
            throw DeliverableShareException::expired([
                ...$context,
                'contract_id' => $link->tm_contract_id,
                'share_link_id' => $link->id,
                'expires_at' => $link->expires_at?->toIso8601String(),
            ]);
        }

        $link->loadMissing(['contract.company', 'contract.currentVersion', 'createdBy']);

        if ($link->contract === null) {
            throw DeliverableShareException::notFound([
                ...$context,
                'share_link_id' => $link->id,
                'missing' => 'contract',
            ]);
        }

        return $link;
    }

    protected function createShareLink(Contract $contract, User $user, string $expiryPreset): ContractShareLink
    {
        $attempts = 0;
        $expiresAt = (new ContractShareLink)->resolveExpiry($expiryPreset);

        while (true) {
            try {
                return ContractShareLink::query()->create([
                    'tm_contract_id' => $contract->id,
                    'token' => bin2hex(random_bytes(32)),
                    'short_code' => ShareShortCodeGenerator::generateUnique(),
                    'expiry_preset' => $expiryPreset,
                    'expires_at' => $expiresAt,
                    'created_by_user_id' => $user->id,
                ]);
            } catch (QueryException $exception) {
                $attempts++;

                if (! $this->isUniqueConstraintViolation($exception) || $attempts >= self::MAX_CREATE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    protected function refreshExpiry(ContractShareLink $link, string $expiryPreset): ContractShareLink
    {
        $link->update([
            'expiry_preset' => $expiryPreset,
            'expires_at' => $link->resolveExpiry($expiryPreset),
            'revoked_at' => null,
        ]);

        return $link->refresh();
    }

    protected function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
