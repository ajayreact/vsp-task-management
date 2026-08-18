<?php

namespace App\Modules\Core\Enums;

/**
 * Accounts live on one `users` table. Staff records use Internal.
 */
enum UserType: string
{
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal staff',
        };
    }
}
