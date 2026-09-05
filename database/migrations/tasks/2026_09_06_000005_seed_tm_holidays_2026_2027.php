<?php

use App\Modules\TaskManagement\Models\Holiday;
use App\Modules\TaskManagement\Support\HolidayCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (HolidayCatalog::definitions() as $row) {
            Holiday::query()->updateOrCreate(
                [
                    'country' => $row['country']->value,
                    'region' => $row['region'],
                    'date' => $row['date'],
                    'name' => $row['name'],
                ],
                [
                    'year' => $row['year'],
                    'holiday_type' => $row['holiday_type'],
                    'description' => $row['description'],
                ],
            );
        }
    }

    public function down(): void
    {
        Holiday::query()
            ->whereIn('year', [2026, 2027])
            ->whereIn('country', ['india', 'usa'])
            ->delete();
    }
};
