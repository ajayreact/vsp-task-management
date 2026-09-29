<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskPriority;
use App\Modules\RecruiterOperations\Enums\RecruiterWorkType;
use App\Modules\RecruiterOperations\Exceptions\RecruiterTaskWorkflowException;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Creating, describing and deleting recruiter tasks. Anything that changes the
 * assignee or the status is handed to RecruiterTaskWorkflow.
 */
class RecruiterTaskService
{
    /**
     * The fields a manager describes a task with. Status, assignee and the
     * workflow timestamps are deliberately absent.
     */
    public const DESCRIPTIVE_FIELDS = [
        'title',
        'description',
        'instructions',
        'work_type',
        'priority',
        'due_at',
        'target_count',
    ];

    public const MAX_COUNT = 100000;

    public function __construct(protected RecruiterTaskWorkflow $workflow) {}

    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'work_type' => ['required', Rule::enum(RecruiterWorkType::class)],
            'priority' => ['required', Rule::enum(RecruiterTaskPriority::class)],
            'due_at' => ['nullable', 'date'],
            'target_count' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_COUNT],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data, Employee $assignee): RecruiterTask
    {
        $task = new RecruiterTask($this->descriptive($data));

        return $this->workflow->open($task, $assignee, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RecruiterTask $task, array $data, User $actor): RecruiterTask
    {
        if ($task->status->isTerminal()) {
            throw RecruiterTaskWorkflowException::cancelled();
        }

        return DB::transaction(function () use ($task, $data, $actor) {
            $task->fill($this->descriptive($data));

            $changes = [];
            foreach (array_keys($task->getDirty()) as $field) {
                $changes[$field] = [
                    'from' => $this->serialise($task->getOriginal($field)),
                    'to' => $this->serialise($task->getAttribute($field)),
                ];
            }

            if ($changes === []) {
                return $task;
            }

            $task->save();

            $task->events()->create([
                'event' => RecruiterTaskEventType::Updated,
                'actor_user_id' => $actor->id,
                'metadata' => ['changes' => $changes],
                'occurred_at' => now(),
            ]);

            return $task;
        });
    }

    /**
     * Only an untouched task can be deleted; once the workflow has been used
     * the task and its history are kept.
     */
    public function delete(RecruiterTask $task): void
    {
        DB::transaction(function () use ($task) {
            $fresh = RecruiterTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if (! $fresh->isDeletable()) {
                throw RecruiterTaskWorkflowException::notDeletable();
            }

            $fresh->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function descriptive(array $data): array
    {
        $attributes = array_intersect_key($data, array_flip(self::DESCRIPTIVE_FIELDS));

        foreach (['description', 'instructions', 'due_at', 'target_count'] as $optional) {
            if (array_key_exists($optional, $attributes) && $attributes[$optional] === '') {
                $attributes[$optional] = null;
            }
        }

        return $attributes;
    }

    protected function serialise(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            default => $value,
        };
    }
}
