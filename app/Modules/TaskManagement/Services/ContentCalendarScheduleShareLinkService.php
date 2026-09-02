<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\ContentCalendarScheduleShareLink;
use App\Modules\TaskManagement\Support\ShareShortCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ContentCalendarScheduleShareLinkService
{
    private const MAX_CREATE_ATTEMPTS = 5;

    public function getOrCreate(Company $company, Carbon $periodStart, Carbon $periodEnd, User $user): ContentCalendarScheduleShareLink
    {
        return DB::transaction(function () use ($company, $periodStart, $periodEnd, $user): ContentCalendarScheduleShareLink {
            $existing = ContentCalendarScheduleShareLink::query()
                ->where('tm_company_id', $company->id)
                ->whereDate('period_start', $periodStart)
                ->whereDate('period_end', $periodEnd)
                ->first();

            if ($existing !== null) {
                return $this->ensureShortCode($existing);
            }

            return $this->createShareLink($company, $periodStart, $periodEnd, $user);
        });
    }

    public function resolveByToken(string $token): ContentCalendarScheduleShareLink
    {
        $link = ContentCalendarScheduleShareLink::query()->where('token', $token)->first();

        return $this->resolveAccessibleLink($link, [
            'identifier_type' => 'legacy_token',
            'token_suffix' => substr($token, -8),
        ]);
    }

    public function resolveByShortCode(string $shortCode): ContentCalendarScheduleShareLink
    {
        $link = ContentCalendarScheduleShareLink::query()->where('short_code', $shortCode)->first();

        return $this->resolveAccessibleLink($link, [
            'identifier_type' => 'short_code',
            'short_code' => $shortCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveAccessibleLink(?ContentCalendarScheduleShareLink $link, array $context = []): ContentCalendarScheduleShareLink
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

    protected function createShareLink(Company $company, Carbon $periodStart, Carbon $periodEnd, User $user): ContentCalendarScheduleShareLink
    {
        $attempts = 0;

        while (true) {
            try {
                return ContentCalendarScheduleShareLink::query()->create([
                    'tm_company_id' => $company->id,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'token' => bin2hex(random_bytes(32)),
                    'short_code' => ShareShortCodeGenerator::generateUnique(),
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

    protected function ensureShortCode(ContentCalendarScheduleShareLink $link): ContentCalendarScheduleShareLink
    {
        if ($link->short_code !== null) {
            return $link;
        }

        $attempts = 0;

        while (true) {
            try {
                $link->update(['short_code' => ShareShortCodeGenerator::generateUnique()]);

                return $link->refresh();
            } catch (QueryException $exception) {
                $attempts++;

                if (! $this->isUniqueConstraintViolation($exception) || $attempts >= self::MAX_CREATE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    protected function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
