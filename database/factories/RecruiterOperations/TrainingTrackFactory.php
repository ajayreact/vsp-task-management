<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Models\TrainingTrack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrainingTrack>
 */
class TrainingTrackFactory extends Factory
{
    /** @var class-string<TrainingTrack> */
    protected $model = TrainingTrack::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Track '.fake()->unique()->numberBetween(1, 999999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => null,
            'status' => TrainingTrack::STATUS_ACTIVE,
            'sort_order' => 10,
        ];
    }
}
