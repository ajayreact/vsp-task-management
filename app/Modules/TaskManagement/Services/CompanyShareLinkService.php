<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyShareLink;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CompanyShareLinkService
{
    private const SHORT_CODE_LENGTH = 8;

    private const SHORT_CODE_MAX_CREATE_ATTEMPTS = 5;

    /**
     * @var non-empty-string
     */
    private const SHORT_CODE_ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public function getOrCreate(Company $company, User $user): CompanyShareLink
    {
        return DB::transaction(function () use ($company, $user): CompanyShareLink {
            Company::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();

            $existing = CompanyShareLink::query()
                ->where('tm_company_id', $company->id)
                ->first();

            if ($existing !== null) {
                return $this->ensureShortCode($existing);
            }

            return $this->createShareLink($company, $user);
        });
    }

    public function resolveByToken(string $token): CompanyShareLink
    {
        $link = CompanyShareLink::query()
            ->where('token', $token)
            ->first();

        return $this->resolveAccessibleLink($link, context: [
            'identifier_type' => 'legacy_token',
            'token_suffix' => substr($token, -8),
        ]);
    }

    public function resolveByShortCode(string $shortCode): CompanyShareLink
    {
        $link = CompanyShareLink::query()
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
    protected function resolveAccessibleLink(?CompanyShareLink $link, array $context = []): CompanyShareLink
    {
        if ($link === null) {
            throw DeliverableShareException::notFound($context);
        }

        if ($link->isRevoked()) {
            throw DeliverableShareException::revoked([
                ...$context,
                'company_id' => $link->tm_company_id,
                'share_link_id' => $link->id,
            ]);
        }

        if ($link->isExpired()) {
            throw DeliverableShareException::expired([
                ...$context,
                'company_id' => $link->tm_company_id,
                'share_link_id' => $link->id,
                'expires_at' => $link->expires_at?->toIso8601String(),
            ]);
        }

        $link->loadMissing(['company', 'createdBy']);

        if ($link->company === null) {
            throw DeliverableShareException::notFound([
                ...$context,
                'share_link_id' => $link->id,
                'missing' => 'company',
            ]);
        }

        return $link;
    }

    protected function createShareLink(Company $company, User $user): CompanyShareLink
    {
        $attempts = 0;

        while (true) {
            try {
                return CompanyShareLink::query()->create([
                    'tm_company_id' => $company->id,
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

    protected function ensureShortCode(CompanyShareLink $link): CompanyShareLink
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
        } while (
            CompanyShareLink::query()->where('short_code', $code)->exists()
            || \App\Modules\TaskManagement\Models\DeliverableShareLink::query()->where('short_code', $code)->exists()
        );

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
