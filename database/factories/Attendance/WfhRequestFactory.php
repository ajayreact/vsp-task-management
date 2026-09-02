<?php

namespace Database\Factories\Attendance;

use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WfhRequest>
 */
class WfhRequestFactory extends Factory
{
    /** @var class-string<WfhRequest> */
    protected $model = WfhRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'reason' => fake()->sentence(),
            'status' => WfhRequestStatus::Pending,
            'approved_by_user_id' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => WfhRequestStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
