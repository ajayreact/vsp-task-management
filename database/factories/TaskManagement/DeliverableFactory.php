<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deliverable>
 */
class DeliverableFactory extends Factory
{
    /** @var class-string<Deliverable> */
    protected $model = Deliverable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tm_task_id' => Task::factory(),
            'version' => 1,
            'submitted_by_employee_id' => Employee::factory(),
            'status' => DeliverableStatus::InReview,
            'notes' => null,
            'submitted_at' => now(),
        ];
    }
}
