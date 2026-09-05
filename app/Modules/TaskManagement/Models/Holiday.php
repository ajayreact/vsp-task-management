<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\TaskManagement\Enums\HolidayCountry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $country
 * @property string|null $region
 * @property string $name
 * @property Carbon $date
 * @property int $year
 * @property string|null $holiday_type
 * @property string|null $description
 */
class Holiday extends Model
{
    protected $table = 'tm_holidays';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'country',
        'region',
        'name',
        'date',
        'year',
        'holiday_type',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'country' => HolidayCountry::class,
            'year' => 'integer',
        ];
    }
}
