<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterActivityType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Records, changes and removes daily activities. The rules live here rather
 * than in the controller or the page:
 *
 * - an activity always belongs to the recruiter who records it;
 * - a recruiter works within today and the previous seven days, a lead with
 *   recruiter.team.view and recruiter.tasks.manage may correct older records;
 * - duration is calculated from start and end time, never taken from input;
 * - a linked task must be one of the activity owner's own recruiter tasks.
 *
 * Ownership is checked against the actor's own employee record, so it holds
 * even where Gate::before would let a policy through.
 */
class RecruiterDailyActivityService
{
    public const MAX_QUANTITY = 100000;

    public const MAX_DURATION_MINUTES = 24 * 60;

    /**
     * Task statuses an activity can be newly linked to. An existing link is
     * kept whatever happens to the task afterwards.
     */
    private const LINKABLE_TASK_STATUSES = [
        RecruiterTaskStatus::Assigned,
        RecruiterTaskStatus::InProgress,
        RecruiterTaskStatus::OnHold,
        RecruiterTaskStatus::Completed,
    ];

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'activity_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'activity_type' => ['required', Rule::enum(RecruiterActivityType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time', 'after:start_time'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_QUANTITY],
            'recruiter_task_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Minutes from start to end on the same day. Negative or zero when end is
     * not after start; the caller decides what to do with that.
     */
    public static function minutesBetween(string $start, string $end): int
    {
        return intdiv(self::secondsOfDay($end) - self::secondsOfDay($start), 60);
    }

    /**
     * Whether the actor may record and correct activities for the whole team,
     * outside the seven-day window.
     */
    public function correctsTeam(User $actor): bool
    {
        return $actor->can('correctsTeam', RecruiterDailyActivity::class);
    }

    /**
     * @param  array<string, mixed>  $data  validated input
     */
    public function create(User $actor, array $data): RecruiterDailyActivity
    {
        $employee = $this->actorEmployee($actor)
            ?? throw new AuthorizationException('Daily activities need an employee profile.');

        $date = Carbon::parse($data['activity_date']);
        $this->assertDateAllowed($date, $actor);
        $taskId = $this->linkableTaskId($data['recruiter_task_id'] ?? null, $employee->id, null);
        $duration = $this->duration($data['start_time'] ?? null, $data['end_time'] ?? null);

        return DB::transaction(function () use ($employee, $actor, $data, $taskId, $duration) {
            $activity = new RecruiterDailyActivity;
            $activity->fill($this->descriptive($data));
            $activity->forceFill([
                'employee_id' => $employee->id,
                'recruiter_task_id' => $taskId,
                'duration_minutes' => $duration,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ])->save();

            return $activity;
        });
    }

    /**
     * @param  array<string, mixed>  $data  validated input
     */
    public function update(RecruiterDailyActivity $activity, array $data, User $actor): RecruiterDailyActivity
    {
        return DB::transaction(function () use ($activity, $data, $actor) {
            $fresh = RecruiterDailyActivity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $this->assertMayModify($fresh, $actor);

            $this->assertDateAllowed(Carbon::parse($data['activity_date']), $actor);
            $taskId = $this->linkableTaskId($data['recruiter_task_id'] ?? null, $fresh->employee_id, $fresh->recruiter_task_id);

            $fresh->fill($this->descriptive($data));
            $fresh->forceFill([
                'recruiter_task_id' => $taskId,
                'duration_minutes' => $this->duration($data['start_time'] ?? null, $data['end_time'] ?? null),
                'updated_by_user_id' => $actor->id,
            ])->save();

            return $fresh;
        });
    }

    public function delete(RecruiterDailyActivity $activity, User $actor): void
    {
        DB::transaction(function () use ($activity, $actor) {
            $fresh = RecruiterDailyActivity::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $this->assertMayModify($fresh, $actor);
            $fresh->delete();
        });
    }

    /**
     * Tasks an activity for this employee may be linked to, plus the task it
     * is already linked to so an edit never silently drops it.
     *
     * @return Collection<int, RecruiterTask>
     */
    public function linkableTasks(int $employeeId, ?int $currentTaskId = null): Collection
    {
        return RecruiterTask::query()
            ->where(function ($query) use ($employeeId, $currentTaskId) {
                $query->where(function ($own) use ($employeeId) {
                    $own->where('assigned_employee_id', $employeeId)
                        ->whereIn('status', array_map(fn (RecruiterTaskStatus $status) => $status->value, self::LINKABLE_TASK_STATUSES));
                });

                if ($currentTaskId !== null) {
                    $query->orWhere('id', $currentTaskId);
                }
            })
            ->orderByRaw("case when status = 'completed' then 1 else 0 end")
            ->orderByDesc('id')
            ->get(['id', 'title', 'status']);
    }

    protected function assertMayModify(RecruiterDailyActivity $activity, User $actor): void
    {
        if ($this->correctsTeam($actor)) {
            return;
        }

        if (! $activity->isOwnedBy($this->actorEmployee($actor))) {
            throw new AuthorizationException('You can only change your own daily activities.');
        }

        if (! $activity->isWithinSelfServiceWindow()) {
            throw new AuthorizationException('Activities older than '.RecruiterDailyActivity::SELF_SERVICE_DAYS.' days can only be corrected by a recruiter lead.');
        }
    }

    protected function assertDateAllowed(CarbonInterface $date, User $actor): void
    {
        if ($date->greaterThan(today())) {
            throw ValidationException::withMessages(['activity_date' => 'Activities cannot be recorded for a future date.']);
        }

        if (! $this->correctsTeam($actor) && $date->lessThan(RecruiterDailyActivity::earliestSelfServiceDate())) {
            throw ValidationException::withMessages([
                'activity_date' => 'You can record activities for today and the previous '.RecruiterDailyActivity::SELF_SERVICE_DAYS.' days only.',
            ]);
        }
    }

    protected function linkableTaskId(mixed $taskId, int $employeeId, ?int $currentTaskId): ?int
    {
        if ($taskId === null || $taskId === '') {
            return null;
        }

        $taskId = (int) $taskId;

        if (! $this->linkableTasks($employeeId, $currentTaskId)->contains('id', $taskId)) {
            throw ValidationException::withMessages(['recruiter_task_id' => 'Choose one of the recruiter\'s own recruiter tasks.']);
        }

        return $taskId;
    }

    protected function duration(?string $start, ?string $end): ?int
    {
        if ($start === null && $end === null) {
            return null;
        }

        if ($start === null || $end === null) {
            throw ValidationException::withMessages(['start_time' => 'Give both a start and an end time, or neither.']);
        }

        $minutes = self::minutesBetween($start, $end);

        if ($minutes <= 0) {
            throw ValidationException::withMessages(['end_time' => 'The end time must be after the start time.']);
        }

        if ($minutes > self::MAX_DURATION_MINUTES) {
            throw ValidationException::withMessages(['end_time' => 'An activity cannot be longer than 24 hours.']);
        }

        return $minutes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function descriptive(array $data): array
    {
        return [
            'activity_date' => $data['activity_date'],
            'activity_type' => $data['activity_type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ];
    }

    protected function actorEmployee(User $actor): ?Employee
    {
        return Employee::query()->where('user_id', $actor->id)->first();
    }

    private static function secondsOfDay(string $time): int
    {
        $parts = array_map('intval', explode(':', $time)) + [0, 0, 0];

        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
    }
}
