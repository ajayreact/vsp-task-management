<?php

namespace App\Modules\TaskManagement\Support;

use App\Modules\TaskManagement\Models\CompanyDocumentShareLink;
use App\Modules\TaskManagement\Models\CompanyShareLink;
use App\Modules\TaskManagement\Models\ContentCalendarItemShareLink;
use App\Modules\TaskManagement\Models\ContentCalendarScheduleShareLink;
use App\Modules\TaskManagement\Models\ContractShareLink;
use App\Modules\TaskManagement\Models\DeliverableShareLink;

class ShareShortCodeGenerator
{
    private const SHORT_CODE_LENGTH = 8;

    /**
     * @var non-empty-string
     */
    private const SHORT_CODE_ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public static function generateUnique(): string
    {
        do {
            $code = self::random();
        } while (self::exists($code));

        return $code;
    }

    public static function exists(string $code): bool
    {
        return CompanyShareLink::query()->where('short_code', $code)->exists()
            || DeliverableShareLink::query()->where('short_code', $code)->exists()
            || CompanyDocumentShareLink::query()->where('short_code', $code)->exists()
            || ContentCalendarItemShareLink::query()->where('short_code', $code)->exists()
            || ContentCalendarScheduleShareLink::query()->where('short_code', $code)->exists()
            || ContractShareLink::query()->where('short_code', $code)->exists();
    }

    protected static function random(): string
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
}
