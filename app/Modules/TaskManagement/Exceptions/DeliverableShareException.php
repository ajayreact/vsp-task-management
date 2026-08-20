<?php

namespace App\Modules\TaskManagement\Exceptions;

use Exception;

class DeliverableShareException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $title,
        public readonly string $userMessage,
        public readonly int $statusCode,
        public readonly string $reason,
        public readonly array $context = [],
    ) {
        parent::__construct($userMessage);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function notFound(array $context = []): self
    {
        return new self(
            title: 'Link Not Found',
            userMessage: 'The link you followed could not be found.',
            statusCode: 404,
            reason: 'not_found',
            context: $context,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function expired(array $context = []): self
    {
        return new self(
            title: 'Link Expired',
            userMessage: 'This shared link has expired.',
            statusCode: 410,
            reason: 'expired',
            context: $context,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function revoked(array $context = []): self
    {
        return new self(
            title: 'Link Unavailable',
            userMessage: 'This shared link is no longer available.',
            statusCode: 403,
            reason: 'revoked',
            context: $context,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function unauthorized(array $context = []): self
    {
        return new self(
            title: 'Access Denied',
            userMessage: 'You do not have permission to access this resource.',
            statusCode: 403,
            reason: 'unauthorized',
            context: $context,
        );
    }

    public static function serverError(): self
    {
        return new self(
            title: 'Something Went Wrong',
            userMessage: 'Something went wrong while loading this page. Please try again later.',
            statusCode: 500,
            reason: 'server_error',
        );
    }
}
