<?php

namespace Database\Factories\Attendance;

use App\Modules\Attendance\Models\OfficeLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeLocation>
 */
class OfficeLocationFactory extends Factory
{
    /** @var class-string<OfficeLocation> */
    protected $model = OfficeLocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Office',
            'address' => fake()->streetAddress().', '.fake()->city(),
            'latitude' => fake()->latitude(8, 37),
            'longitude' => fake()->longitude(68, 97),
            'allowed_gps_radius_meters' => fake()->numberBetween(50, 500),
            'late_check_in_time' => '23:59:59',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
