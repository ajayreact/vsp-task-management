<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\TimeSource;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /** @var class-string<TimeEntry> */
    protected $model = TimeEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $started = now()->subHours(2);

        return [
            'tm_task_id' => Task::factory(),
            'employee_id' => Employee::factory(),
            'started_at' => $started,
            'ended_at' => $started->copy()->addHour(),
            'duration_seconds' => 3600,
            'is_running' => false,
            'source' => TimeSource::Manual,
            'note' => null,
            'is_billable' => true,
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'ended_at' => null,
            'duration_seconds' => 0,
            'is_running' => true,
            'source' => TimeSource::Timer,
        ]);
    }
}
