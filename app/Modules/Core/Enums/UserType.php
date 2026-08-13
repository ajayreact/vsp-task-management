<?php

namespace App\Modules\Core\Enums;

/**
 * Staff and client-portal accounts share the `users` table. This discriminator
 * is what the portal tenant scope and the internal-only routes key off.
 */
enum UserType: string
{
    case Internal = 'internal';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal staff',
            self::Client => 'Client',
        };
    }
}
