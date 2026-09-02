<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentCalendarItem>
 */
class ContentCalendarItemFactory extends Factory
{
    /** @var class-string<ContentCalendarItem> */
    protected $model = ContentCalendarItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tm_company_id' => Company::factory(),
            'scheduled_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'scheduled_time' => fake()->optional()->time('H:i'),
            'content_type' => fake()->randomElement(ContentCalendarType::cases()),
            'platform' => fake()->randomElement(ContentCalendarPlatform::cases()),
            'description' => fake()->sentence(),
            'status' => ContentCalendarStatus::Draft,
            'internal_notes' => fake()->optional()->sentence(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
