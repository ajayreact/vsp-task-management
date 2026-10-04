<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Builds a bare course row. Use TrainingCourseVersionFactory to add content;
 * application code goes through TrainingContentService.
 *
 * @extends Factory<TrainingCourse>
 */
class TrainingCourseFactory extends Factory
{
    /** @var class-string<TrainingCourse> */
    protected $model = TrainingCourse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'U.S. Time Zones '.fake()->unique()->numberBetween(1, 999999);

        return [
            'category_id' => TrainingCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => null,
            'status' => TrainingContentStatus::Draft,
            'current_version_id' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }

    public function inTrack(TrainingTrack $track): static
    {
        return $this->state(fn () => ['training_track_id' => $track->id]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => TrainingContentStatus::Archived]);
    }
}
