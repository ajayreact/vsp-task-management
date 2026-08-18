<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Shared page-size resolution for list screens. Keeps per_page in a fixed set so
 * the "Show entries" control cannot be abused into huge result sets.
 */
final class Pagination
{
    /** @var list<int> */
    public const ALLOWED = [7, 10, 15, 20, 25, 50, 100];

    public static function perPage(Request $request, int $default = 15): int
    {
        $value = $request->integer('per_page', $default);

        return in_array($value, self::ALLOWED, true) ? $value : $default;
    }
}
