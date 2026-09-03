<?php

namespace Database\Factories\Attendance;

use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Enums\WfhRequestType;
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
        $start = fake()->dateTimeBetween('now', '+2 weeks');

        return [
            'employee_id' => Employee::factory(),
            'type' => WfhRequestType::Request,
            'start_date' => $start,
            'end_date' => $start,
            'reason' => fake()->sentence(),
            'notes' => null,
            'status' => WfhRequestStatus::Pending,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'requested_by_user_id' => null,
            'assigned_by_user_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => WfhRequestStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'type' => WfhRequestType::Assignment,
            'status' => WfhRequestStatus::Assigned,
            'approved_at' => now(),
        ]);
    }
}
