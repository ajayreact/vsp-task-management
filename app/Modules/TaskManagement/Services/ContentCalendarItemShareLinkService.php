<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\ContentCalendarItemShareLink;
use App\Modules\TaskManagement\Support\ShareShortCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ContentCalendarItemShareLinkService
{
    private const MAX_CREATE_ATTEMPTS = 5;

    public function getOrCreate(ContentCalendarItem $item, User $user): ContentCalendarItemShareLink
    {
        return DB::transaction(function () use ($item, $user): ContentCalendarItemShareLink {
            ContentCalendarItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            $existing = ContentCalendarItemShareLink::query()
                ->where('tm_content_calendar_item_id', $item->id)
                ->first();

            if ($existing !== null) {
                return $this->ensureShortCode($existing);
            }

            return $this->createShareLink($item, $user);
        });
    }

    public function resolveByToken(string $token): ContentCalendarItemShareLink
    {
        $link = ContentCalendarItemShareLink::query()->where('token', $token)->first();

        return $this->resolveAccessibleLink($link, [
            'identifier_type' => 'legacy_token',
            'token_suffix' => substr($token, -8),
        ]);
    }

    public function resolveByShortCode(string $shortCode): ContentCalendarItemShareLink
    {
        $link = ContentCalendarItemShareLink::query()->where('short_code', $shortCode)->first();

        return $this->resolveAccessibleLink($link, [
            'identifier_type' => 'short_code',
            'short_code' => $shortCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveAccessibleLink(?ContentCalendarItemShareLink $link, array $context = []): ContentCalendarItemShareLink
    {
        if ($link === null) {
            throw DeliverableShareException::notFound($context);
        }

        if ($link->isRevoked()) {
            throw DeliverableShareException::revoked([
                ...$context,
                'content_item_id' => $link->tm_content_calendar_item_id,
                'share_link_id' => $link->id,
            ]);
        }

        if ($link->isExpired()) {
            throw DeliverableShareException::expired([
                ...$context,
                'content_item_id' => $link->tm_content_calendar_item_id,
                'share_link_id' => $link->id,
                'expires_at' => $link->expires_at?->toIso8601String(),
            ]);
        }

        $link->loadMissing(['item.company', 'createdBy']);

        if ($link->item === null) {
            throw DeliverableShareException::notFound([
                ...$context,
                'share_link_id' => $link->id,
                'missing' => 'content_item',
            ]);
        }

        return $link;
    }

    protected function createShareLink(ContentCalendarItem $item, User $user): ContentCalendarItemShareLink
    {
        $attempts = 0;

        while (true) {
            try {
                return ContentCalendarItemShareLink::query()->create([
                    'tm_content_calendar_item_id' => $item->id,
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

    protected function ensureShortCode(ContentCalendarItemShareLink $link): ContentCalendarItemShareLink
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
