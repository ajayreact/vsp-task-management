<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Models\TrainingCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Builds rows directly for test setup. Application code manages categories
 * through TrainingContentService.
 *
 * @extends Factory<TrainingCategory>
 */
class TrainingCategoryFactory extends Factory
{
    /** @var class-string<TrainingCategory> */
    protected $model = TrainingCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Level '.fake()->unique()->numberBetween(1, 9999).' - Recruiter Basics';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => null,
            'level_number' => null,
            'sort_order' => 0,
            'is_active' => true,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
