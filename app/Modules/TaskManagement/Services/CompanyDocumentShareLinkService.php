<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Models\CompanyDocumentShareLink;
use App\Modules\TaskManagement\Support\ShareShortCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CompanyDocumentShareLinkService
{
    private const MAX_CREATE_ATTEMPTS = 5;

    public function getOrCreate(CompanyDocument $document, User $user): CompanyDocumentShareLink
    {
        return DB::transaction(function () use ($document, $user): CompanyDocumentShareLink {
            CompanyDocument::query()->whereKey($document->id)->lockForUpdate()->firstOrFail();

            $existing = CompanyDocumentShareLink::query()
                ->where('tm_company_document_id', $document->id)
                ->first();

            if ($existing !== null) {
                return $this->ensureShortCode($existing);
            }

            return $this->createShareLink($document, $user);
        });
    }

    public function resolveByToken(string $token): CompanyDocumentShareLink
    {
        $link = CompanyDocumentShareLink::query()->where('token', $token)->first();

        return $this->resolveAccessibleLink($link, [
            'identifier_type' => 'legacy_token',
            'token_suffix' => substr($token, -8),
        ]);
    }

    public function resolveByShortCode(string $shortCode): CompanyDocumentShareLink
    {
        $link = CompanyDocumentShareLink::query()->where('short_code', $shortCode)->first();

        return $this->resolveAccessibleLink($link, [
            'identifier_type' => 'short_code',
            'short_code' => $shortCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveAccessibleLink(?CompanyDocumentShareLink $link, array $context = []): CompanyDocumentShareLink
    {
        if ($link === null) {
            throw DeliverableShareException::notFound($context);
        }

        if ($link->isRevoked()) {
            throw DeliverableShareException::revoked([
                ...$context,
                'document_id' => $link->tm_company_document_id,
                'share_link_id' => $link->id,
            ]);
        }

        if ($link->isExpired()) {
            throw DeliverableShareException::expired([
                ...$context,
                'document_id' => $link->tm_company_document_id,
                'share_link_id' => $link->id,
                'expires_at' => $link->expires_at?->toIso8601String(),
            ]);
        }

        $link->loadMissing(['document.company', 'createdBy']);

        if ($link->document === null) {
            throw DeliverableShareException::notFound([
                ...$context,
                'share_link_id' => $link->id,
                'missing' => 'document',
            ]);
        }

        return $link;
    }

    protected function createShareLink(CompanyDocument $document, User $user): CompanyDocumentShareLink
    {
        $attempts = 0;

        while (true) {
            try {
                return CompanyDocumentShareLink::query()->create([
                    'tm_company_document_id' => $document->id,
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

    protected function ensureShortCode(CompanyDocumentShareLink $link): CompanyDocumentShareLink
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
