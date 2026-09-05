<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarTopic;
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
            'topic' => ContentCalendarTopic::Other,
            'description' => fake()->sentence(),
            'caption' => fake()->optional()->sentence(),
            'hashtags' => fake()->optional()->words(3, true),
            'post_number' => null,
            'status' => ContentCalendarStatus::Draft,
            'internal_notes' => fake()->optional()->sentence(),
            'created_by_user_id' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ContentCalendarItem $item): void {
            if ($item->platforms()->exists()) {
                return;
            }

            $item->syncPlatforms([
                fake()->randomElement(ContentCalendarPlatform::cases())->value,
            ]);
        });
    }
}
