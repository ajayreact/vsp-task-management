<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterActivityType;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds rows directly for test setup. Application code records activities
 * through RecruiterDailyActivityService.
 *
 * @extends Factory<RecruiterDailyActivity>
 */
class RecruiterDailyActivityFactory extends Factory
{
    /** @var class-string<RecruiterDailyActivity> */
    protected $model = RecruiterDailyActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'activity_date' => today(),
            'activity_type' => RecruiterActivityType::LinkedinSourcing,
            'title' => 'LinkedIn sourcing for STEM OPT roles',
            'description' => null,
            'start_time' => null,
            'end_time' => null,
            'duration_minutes' => null,
            'recruiter_task_id' => null,
            'quantity' => null,
            'remarks' => null,
            'created_by_user_id' => User::factory(),
            'updated_by_user_id' => null,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(fn () => [
            'employee_id' => $employee->id,
            'created_by_user_id' => $employee->user_id,
        ]);
    }

    public function today(): static
    {
        return $this->state(fn () => ['activity_date' => today()]);
    }

    public function yesterday(): static
    {
        return $this->state(fn () => ['activity_date' => today()->subDay()]);
    }

    public function daysAgo(int $days): static
    {
        return $this->state(fn () => ['activity_date' => today()->subDays($days)]);
    }

    public function withTask(?RecruiterTask $task = null): static
    {
        return $this->state(fn (array $attributes) => [
            'recruiter_task_id' => $task->id ?? RecruiterTask::factory()->state([
                'assigned_employee_id' => $attributes['employee_id'],
            ]),
        ]);
    }

    public function withoutTask(): static
    {
        return $this->state(fn () => ['recruiter_task_id' => null]);
    }

    public function withDuration(string $start = '09:30', string $end = '10:30'): static
    {
        $minutes = (int) ((strtotime($end) - strtotime($start)) / 60);

        return $this->state(fn () => [
            'start_time' => $start,
            'end_time' => $end,
            'duration_minutes' => $minutes,
        ]);
    }

    public function withQuantity(int $quantity = 10): static
    {
        return $this->state(fn () => ['quantity' => $quantity]);
    }
}
