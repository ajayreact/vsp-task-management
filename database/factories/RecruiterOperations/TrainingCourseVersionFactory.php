<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds version rows for test setup. published() also marks the course as
 * published with this as its current version, as TrainingContentService does.
 *
 * @extends Factory<TrainingCourseVersion>
 */
class TrainingCourseVersionFactory extends Factory
{
    /** @var class-string<TrainingCourseVersion> */
    protected $model = TrainingCourseVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => TrainingCourse::factory(),
            'version_number' => 1,
            'status' => TrainingContentStatus::Draft,
            'description' => null,
            'estimated_minutes' => null,
            'published_at' => null,
            'created_by_user_id' => null,
        ];
    }

    public function forCourse(TrainingCourse $course, int $number = 1): static
    {
        return $this->state(fn () => ['course_id' => $course->id, 'version_number' => $number]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => TrainingContentStatus::Published,
            'published_at' => now(),
        ])->afterCreating(function (TrainingCourseVersion $version) {
            $version->course->forceFill([
                'status' => TrainingContentStatus::Published,
                'current_version_id' => $version->id,
            ])->save();
        });
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => TrainingContentStatus::Archived,
            'published_at' => now()->subMonth(),
        ]);
    }
}
