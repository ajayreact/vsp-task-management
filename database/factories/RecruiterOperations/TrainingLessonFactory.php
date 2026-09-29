<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Enums\TrainingLessonContentType;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Builds lesson rows for test setup. Application code goes through
 * TrainingContentService.
 *
 * @extends Factory<TrainingLesson>
 */
class TrainingLessonFactory extends Factory
{
    /** @var class-string<TrainingLesson> */
    protected $model = TrainingLesson::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Lesson '.fake()->unique()->numberBetween(1, 999999);

        return [
            'course_version_id' => TrainingCourseVersion::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => null,
            'sort_order' => 1,
            'content_type' => TrainingLessonContentType::Text,
            'body' => 'The United States spans several time zones.',
            'duration_minutes' => 10,
            'is_required' => true,
            'external_url' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }

    public function forVersion(TrainingCourseVersion $version, int $sortOrder = 1): static
    {
        return $this->state(fn () => ['course_version_id' => $version->id, 'sort_order' => $sortOrder]);
    }

    public function optional(): static
    {
        return $this->state(fn () => ['is_required' => false]);
    }
}
