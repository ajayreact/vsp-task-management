<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Models\Timesheet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Timesheet>
 */
class TimesheetFactory extends Factory
{
    /** @var class-string<Timesheet> */
    protected $model = Timesheet::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::now()->startOfWeek(Carbon::MONDAY);

        return [
            'employee_id' => Employee::factory(),
            'period_start' => $start->toDateString(),
            'period_end' => $start->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            'total_hours' => 0,
            'status' => TimesheetStatus::Draft,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => TimesheetStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }
}
