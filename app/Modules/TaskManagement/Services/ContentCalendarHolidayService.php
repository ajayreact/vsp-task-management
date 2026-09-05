<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Enums\ContentCalendarTopic;
use App\Modules\TaskManagement\Enums\HolidayCountry;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\Holiday;
use Illuminate\Support\Carbon;

class ContentCalendarHolidayService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forCompanyMonth(Company $company, Carbon $monthStart, Carbon $monthEnd): array
    {
        $countries = [];

        if ($company->holiday_india_enabled) {
            $countries[] = HolidayCountry::India->value;
        }

        if ($company->holiday_usa_enabled) {
            $countries[] = HolidayCountry::Usa->value;
        }

        if ($countries === []) {
            return [];
        }

        $plannedDates = ContentCalendarItem::query()
            ->where('tm_company_id', $company->id)
            ->where('topic', ContentCalendarTopic::FestivalHoliday->value)
            ->whereDate('scheduled_date', '>=', $monthStart)
            ->whereDate('scheduled_date', '<=', $monthEnd)
            ->pluck('scheduled_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        return Holiday::query()
            ->whereIn('country', $countries)
            ->whereDate('date', '>=', $monthStart)
            ->whereDate('date', '<=', $monthEnd)
            ->orderBy('date')
            ->get()
            ->map(function (Holiday $holiday) use ($plannedDates): array {
                $date = $holiday->date->toDateString();
                $country = $holiday->country instanceof HolidayCountry
                    ? $holiday->country
                    : HolidayCountry::from((string) $holiday->country);

                return [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'date' => $date,
                    'day' => $holiday->date->format('D'),
                    'year' => $holiday->year,
                    'country' => $country->value,
                    'country_label' => $country->label(),
                    'flag' => $country->flagEmoji(),
                    'holiday_type' => $holiday->holiday_type,
                    'description' => $holiday->description,
                    'is_weekend' => $holiday->date->isWeekend(),
                    'has_planned_post' => in_array($date, $plannedDates, true),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Upcoming holidays from today through the next N days (and rest of selected month).
     *
     * @return list<array<string, mixed>>
     */
    public function upcoming(Company $company, Carbon $from, int $days = 60): array
    {
        $end = $from->copy()->addDays($days);

        return collect($this->forCompanyMonth($company, $from->copy()->startOfDay(), $end))
            ->filter(fn (array $row) => $row['date'] >= $from->toDateString())
            ->take(8)
            ->values()
            ->all();
    }
}
