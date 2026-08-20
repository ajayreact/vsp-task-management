<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\DeliverableShareLink;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DeliverableShareLinkService
{
    private const SHORT_CODE_LENGTH = 8;

    private const SHORT_CODE_MAX_CREATE_ATTEMPTS = 5;

    /**
     * @var non-empty-string
     */
    private const SHORT_CODE_ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Return the existing share link for the deliverable, or create one.
     * Repeated calls for the same deliverable reuse the same token and row.
     */
    public function getOrCreate(Deliverable $deliverable, User $user): DeliverableShareLink
    {
        return DB::transaction(function () use ($deliverable, $user): DeliverableShareLink {
            Deliverable::query()->whereKey($deliverable->id)->lockForUpdate()->firstOrFail();

            $existing = DeliverableShareLink::query()
                ->where('tm_deliverable_id', $deliverable->id)
                ->first();

            if ($existing !== null) {
                return $this->ensureShortCode($existing);
            }

            return $this->createShareLink($deliverable, $user);
        });
    }

    public function resolveByToken(string $token): DeliverableShareLink
    {
        $link = DeliverableShareLink::query()
            ->where('token', $token)
            ->first();

        return $this->resolveAccessibleLink($link, context: [
            'identifier_type' => 'legacy_token',
            'token_suffix' => substr($token, -8),
        ]);
    }

    public function resolveByShortCode(string $shortCode): DeliverableShareLink
    {
        $link = DeliverableShareLink::query()
            ->where('short_code', $shortCode)
            ->first();

        return $this->resolveAccessibleLink($link, context: [
            'identifier_type' => 'short_code',
            'short_code' => $shortCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveAccessibleLink(?DeliverableShareLink $link, array $context = []): DeliverableShareLink
    {
        if ($link === null) {
            throw DeliverableShareException::notFound($context);
        }

        if ($link->isRevoked()) {
            throw DeliverableShareException::revoked([
                ...$context,
                'deliverable_id' => $link->tm_deliverable_id,
                'share_link_id' => $link->id,
            ]);
        }

        if ($link->isExpired()) {
            throw DeliverableShareException::expired([
                ...$context,
                'deliverable_id' => $link->tm_deliverable_id,
                'share_link_id' => $link->id,
                'expires_at' => $link->expires_at?->toIso8601String(),
            ]);
        }

        $link->loadMissing(['deliverable.task.project.company', 'createdBy']);

        if ($link->deliverable === null) {
            throw DeliverableShareException::notFound([
                ...$context,
                'share_link_id' => $link->id,
                'missing' => 'deliverable',
            ]);
        }

        return $link;
    }

    protected function createShareLink(Deliverable $deliverable, User $user): DeliverableShareLink
    {
        $attempts = 0;

        while (true) {
            try {
                return DeliverableShareLink::query()->create([
                    'tm_deliverable_id' => $deliverable->id,
                    'token' => bin2hex(random_bytes(32)),
                    'short_code' => $this->generateUniqueShortCode(),
                    'created_by_user_id' => $user->id,
                ]);
            } catch (QueryException $exception) {
                $attempts++;

                if (! $this->isUniqueConstraintViolation($exception) || $attempts >= self::SHORT_CODE_MAX_CREATE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    protected function ensureShortCode(DeliverableShareLink $link): DeliverableShareLink
    {
        if ($link->short_code !== null) {
            return $link;
        }

        $attempts = 0;

        while (true) {
            try {
                $link->update([
                    'short_code' => $this->generateUniqueShortCode(),
                ]);

                return $link->refresh();
            } catch (QueryException $exception) {
                $attempts++;

                if (! $this->isUniqueConstraintViolation($exception) || $attempts >= self::SHORT_CODE_MAX_CREATE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    protected function generateUniqueShortCode(): string
    {
        do {
            $code = $this->randomShortCode();
        } while (DeliverableShareLink::query()->where('short_code', $code)->exists());

        return $code;
    }

    protected function randomShortCode(): string
    {
        $alphabet = self::SHORT_CODE_ALPHABET;
        $alphabetLength = strlen($alphabet);
        $bytes = random_bytes(self::SHORT_CODE_LENGTH);
        $code = '';

        for ($i = 0; $i < self::SHORT_CODE_LENGTH; $i++) {
            $code .= $alphabet[ord($bytes[$i]) % $alphabetLength];
        }

        return $code;
    }

    protected function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
