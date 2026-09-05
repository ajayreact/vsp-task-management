<?php

namespace App\Modules\TaskManagement\Support;

use App\Modules\TaskManagement\Enums\HolidayCountry;

/**
 * Explicit India + USA holiday catalog for seeding.
 *
 * Coverage years: 2026 and 2027.
 * Scope: major national / widely observed festivals only (region = null).
 * Festival dates for lunar calendars are fixed for these years and may need annual refresh.
 */
final class HolidayCatalog
{
    /**
     * @return list<array{
     *     country: HolidayCountry,
     *     region: null,
     *     name: string,
     *     date: string,
     *     year: int,
     *     holiday_type: string,
     *     description: string|null
     * }>
     */
    public static function definitions(): array
    {
        $rows = [];

        foreach ([2026, 2027] as $year) {
            foreach (self::indiaForYear($year) as $row) {
                $rows[] = $row;
            }
            foreach (self::usaForYear($year) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * India — national + major festivals (2026–2027).
     *
     * 2026: Republic Day, Holi (Mar 3), Good Friday (Apr 3), Eid ul-Fitr (Mar 21),
     * Independence Day, Raksha Bandhan (Aug 28), Janmashtami (Sep 4), Ganesh Chaturthi (Sep 14),
     * Gandhi Jayanti, Dussehra (Oct 20), Diwali (Nov 8), Christmas.
     *
     * 2027: Republic Day, Holi (Mar 22), Good Friday (Mar 26), Eid ul-Fitr (Mar 10),
     * Independence Day, Raksha Bandhan (Aug 17), Janmashtami (Aug 25), Ganesh Chaturthi (Sep 4),
     * Gandhi Jayanti, Dussehra (Oct 9), Diwali (Oct 29), Christmas.
     *
     * @return list<array{country: HolidayCountry, region: null, name: string, date: string, year: int, holiday_type: string, description: string|null}>
     */
    public static function indiaForYear(int $year): array
    {
        $byYear = [
            2026 => [
                ['Republic Day', '2026-01-26', 'national', 'India Republic Day'],
                ['Holi', '2026-03-03', 'festival', 'Festival of Colors'],
                ['Eid ul-Fitr', '2026-03-21', 'festival', 'End of Ramadan (approximate observances may vary)'],
                ['Good Friday', '2026-04-03', 'observance', null],
                ['Independence Day', '2026-08-15', 'national', 'India Independence Day'],
                ['Raksha Bandhan', '2026-08-28', 'festival', null],
                ['Janmashtami', '2026-09-04', 'festival', null],
                ['Ganesh Chaturthi', '2026-09-14', 'festival', null],
                ['Gandhi Jayanti', '2026-10-02', 'national', null],
                ['Dussehra', '2026-10-20', 'festival', 'Vijayadashami'],
                ['Diwali', '2026-11-08', 'festival', 'Deepavali'],
                ['Christmas', '2026-12-25', 'observance', null],
            ],
            2027 => [
                ['Republic Day', '2027-01-26', 'national', 'India Republic Day'],
                ['Eid ul-Fitr', '2027-03-10', 'festival', 'End of Ramadan (approximate observances may vary)'],
                ['Holi', '2027-03-22', 'festival', 'Festival of Colors'],
                ['Good Friday', '2027-03-26', 'observance', null],
                ['Independence Day', '2027-08-15', 'national', 'India Independence Day'],
                ['Raksha Bandhan', '2027-08-17', 'festival', null],
                ['Janmashtami', '2027-08-25', 'festival', null],
                ['Ganesh Chaturthi', '2027-09-04', 'festival', null],
                ['Gandhi Jayanti', '2027-10-02', 'national', null],
                ['Dussehra', '2027-10-09', 'festival', 'Vijayadashami'],
                ['Diwali', '2027-10-29', 'festival', 'Deepavali'],
                ['Christmas', '2027-12-25', 'observance', null],
            ],
        ];

        return array_map(
            fn (array $row) => [
                'country' => HolidayCountry::India,
                'region' => null,
                'name' => $row[0],
                'date' => $row[1],
                'year' => $year,
                'holiday_type' => $row[2],
                'description' => $row[3],
            ],
            $byYear[$year] ?? [],
        );
    }

    /**
     * USA — federal holidays (2026–2027).
     *
     * Includes: New Year's Day, MLK Day, Presidents' Day, Memorial Day, Juneteenth,
     * Independence Day, Labor Day, Columbus Day, Veterans Day, Thanksgiving, Christmas.
     *
     * @return list<array{country: HolidayCountry, region: null, name: string, date: string, year: int, holiday_type: string, description: string|null}>
     */
    public static function usaForYear(int $year): array
    {
        $byYear = [
            2026 => [
                ["New Year's Day", '2026-01-01', 'federal', null],
                ['Martin Luther King Jr. Day', '2026-01-19', 'federal', null],
                ["Presidents' Day", '2026-02-16', 'federal', null],
                ['Memorial Day', '2026-05-25', 'federal', null],
                ['Juneteenth', '2026-06-19', 'federal', null],
                ['Independence Day', '2026-07-04', 'federal', null],
                ['Labor Day', '2026-09-07', 'federal', null],
                ['Columbus Day', '2026-10-12', 'federal', null],
                ['Veterans Day', '2026-11-11', 'federal', null],
                ['Thanksgiving', '2026-11-26', 'federal', null],
                ['Christmas', '2026-12-25', 'federal', null],
            ],
            2027 => [
                ["New Year's Day", '2027-01-01', 'federal', null],
                ['Martin Luther King Jr. Day', '2027-01-18', 'federal', null],
                ["Presidents' Day", '2027-02-15', 'federal', null],
                ['Memorial Day', '2027-05-31', 'federal', null],
                ['Juneteenth', '2027-06-19', 'federal', null],
                ['Independence Day', '2027-07-04', 'federal', null],
                ['Labor Day', '2027-09-06', 'federal', null],
                ['Columbus Day', '2027-10-11', 'federal', null],
                ['Veterans Day', '2027-11-11', 'federal', null],
                ['Thanksgiving', '2027-11-25', 'federal', null],
                ['Christmas', '2027-12-25', 'federal', null],
            ],
        ];

        return array_map(
            fn (array $row) => [
                'country' => HolidayCountry::Usa,
                'region' => null,
                'name' => $row[0],
                'date' => $row[1],
                'year' => $year,
                'holiday_type' => $row[2],
                'description' => $row[3],
            ],
            $byYear[$year] ?? [],
        );
    }
}
