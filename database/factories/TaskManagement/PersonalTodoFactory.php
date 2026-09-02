<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\PersonalTodoStatus;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Models\PersonalTodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalTodo>
 */
class PersonalTodoFactory extends Factory
{
    /** @var class-string<PersonalTodo> */
    protected $model = PersonalTodo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucfirst(fake()->sentence(4)),
            'note' => fake()->optional()->sentence(),
            'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'due_time' => fake()->optional()->time('H:i'),
            'priority' => TaskPriority::Normal,
            'status' => PersonalTodoStatus::Pending,
            'completed_at' => null,
            'sort_order' => null,
            'reminder_at' => null,
            'reminder_sent_at' => null,
            'tm_task_id' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => PersonalTodoStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
