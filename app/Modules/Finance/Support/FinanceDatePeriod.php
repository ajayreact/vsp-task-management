<?php

namespace App\Modules\Finance\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class FinanceDatePeriod
{
    public const THIS_MONTH = 'this_month';

    public const LAST_MONTH = 'last_month';

    public const MONTH = 'month';

    public const THIS_YEAR = 'this_year';

    public const CUSTOM = 'custom';

    public const ALL = 'all';

    /**
     * @return array{period: string, date_from: string|null, date_to: string|null, month: string|null, label: string}
     */
    public static function resolve(Request $request, string $default = self::THIS_MONTH): array
    {
        $period = $request->string('period')->trim()->value();
        if ($period === '') {
            $period = $default;
        }

        $monthParam = $request->string('month')->trim()->value();
        if ($period === self::MONTH || preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
            if (preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
                $monthParam = $period;
            }
            $period = self::MONTH;
        }

        $allowed = [self::THIS_MONTH, self::LAST_MONTH, self::MONTH, self::THIS_YEAR, self::CUSTOM, self::ALL];
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
                'month' => now()->format('Y-m'),
                'label' => now()->format('F Y'),
            ],
            self::LAST_MONTH => [
                'period' => self::LAST_MONTH,
                'date_from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'date_to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                'month' => now()->subMonthNoOverflow()->format('Y-m'),
                'label' => now()->subMonthNoOverflow()->format('F Y'),
            ],
            self::MONTH => self::resolveMonth($monthParam !== '' ? $monthParam : now()->format('Y-m')),
            self::THIS_YEAR => [
                'period' => self::THIS_YEAR,
                'date_from' => now()->startOfYear()->toDateString(),
                'date_to' => now()->endOfYear()->toDateString(),
                'month' => null,
                'label' => 'This Year',
            ],
            self::CUSTOM => [
                'period' => self::CUSTOM,
                'date_from' => self::validDate($customFrom),
                'date_to' => self::validDate($customTo),
                'month' => null,
                'label' => 'Custom range',
            ],
            default => [
                'period' => self::ALL,
                'date_from' => null,
                'date_to' => null,
                'month' => null,
                'label' => 'All Time',
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
            ['value' => self::MONTH, 'label' => 'Specific Month'],
            ['value' => self::THIS_YEAR, 'label' => 'This Year'],
            ['value' => self::CUSTOM, 'label' => 'Custom Date Range'],
            ['value' => self::ALL, 'label' => 'All Time'],
        ];
    }

    /**
     * @return array{period: string, date_from: string|null, date_to: string|null, month: string|null, label: string}
     */
    private static function resolveMonth(string $month): array
    {
        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $start = now()->startOfMonth();
        }

        return [
            'period' => self::MONTH,
            'date_from' => $start->toDateString(),
            'date_to' => $start->copy()->endOfMonth()->toDateString(),
            'month' => $start->format('Y-m'),
            'label' => $start->format('F Y'),
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
