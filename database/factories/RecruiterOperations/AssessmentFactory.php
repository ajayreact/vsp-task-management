<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Enums\AssessmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentType;
use App\Modules\RecruiterOperations\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Builds assessment rows for test setup. Application code goes through
 * AssessmentContentService.
 *
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    /** @var class-string<Assessment> */
    protected $model = Assessment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Immigration Basics Quiz '.fake()->unique()->numberBetween(1, 999999);

        return [
            'type' => AssessmentType::TrainingQuiz,
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => null,
            'status' => AssessmentStatus::Draft,
            'current_version_id' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }
}
