<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Enums\AssessmentStatus;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds version rows for test setup. published() also marks the assessment
 * published with this as its current version, as AssessmentContentService does.
 *
 * @extends Factory<AssessmentVersion>
 */
class AssessmentVersionFactory extends Factory
{
    /** @var class-string<AssessmentVersion> */
    protected $model = AssessmentVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'version_number' => 1,
            'status' => AssessmentStatus::Draft,
            'instructions' => null,
            'passing_percentage' => 70,
            'time_limit_minutes' => null,
            'max_attempts' => 2,
            'randomize_questions' => false,
            'randomize_options' => false,
            'show_result' => true,
            'allow_review' => false,
            'published_at' => null,
            'created_by_user_id' => null,
        ];
    }

    public function forAssessment(Assessment $assessment, int $number = 1): static
    {
        return $this->state(fn () => ['assessment_id' => $assessment->id, 'version_number' => $number]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => AssessmentStatus::Published,
            'published_at' => now(),
        ])->afterCreating(function (AssessmentVersion $version) {
            $version->assessment->forceFill([
                'status' => AssessmentStatus::Published,
                'current_version_id' => $version->id,
            ])->save();
        });
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => AssessmentStatus::Archived,
            'published_at' => now()->subMonth(),
        ]);
    }
}
