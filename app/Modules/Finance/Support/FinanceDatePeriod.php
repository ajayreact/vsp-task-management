<?php

namespace App\Modules\Finance\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class FinanceDatePeriod
{
    public const THIS_MONTH = 'this_month';

    public const LAST_MONTH = 'last_month';

    public const THIS_YEAR = 'this_year';

    public const CUSTOM = 'custom';

    public const ALL = 'all';

    /**
     * @return array{period: string, date_from: string|null, date_to: string|null}
     */
    public static function resolve(Request $request, string $default = self::THIS_MONTH): array
    {
        $period = $request->string('period')->trim()->value();
        if ($period === '') {
            $period = $default;
        }

        $allowed = [self::THIS_MONTH, self::LAST_MONTH, self::THIS_YEAR, self::CUSTOM, self::ALL];
        if (! in_array($period, $allowed, true)) {
            $period = $default;
        }

        $customFrom = $request->string('date_from')->trim()->value();
        $customTo = $request->string('date_to')->trim()->value();

        return match ($period) {
            self::THIS_MONTH => [
                'period' => self::THIS_MONTH,
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ],
            self::LAST_MONTH => [
                'period' => self::LAST_MONTH,
                'date_from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'date_to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            self::THIS_YEAR => [
                'period' => self::THIS_YEAR,
                'date_from' => now()->startOfYear()->toDateString(),
                'date_to' => now()->endOfYear()->toDateString(),
            ],
            self::CUSTOM => [
                'period' => self::CUSTOM,
                'date_from' => self::validDate($customFrom),
                'date_to' => self::validDate($customTo),
            ],
            default => [
                'period' => self::ALL,
                'date_from' => null,
                'date_to' => null,
            ],
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => self::THIS_MONTH, 'label' => 'This Month'],
            ['value' => self::LAST_MONTH, 'label' => 'Last Month'],
            ['value' => self::THIS_YEAR, 'label' => 'This Year'],
            ['value' => self::CUSTOM, 'label' => 'Custom Date Range'],
            ['value' => self::ALL, 'label' => 'All Time'],
        ];
    }

    private static function validDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
