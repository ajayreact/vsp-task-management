<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Enums\TimeSource;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TimeEntry;
use App\Modules\TaskManagement\Models\Timesheet;
use App\Modules\TaskManagement\Support\WorkWeek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Every interval — timed or typed — goes through here so the unique running
 * timer and the weekly timesheet stay consistent.
 */
class TimeTracker
{
    public function __construct(protected TaskWorkflow $workflow) {}

    public function start(Task $task, Employee $employee, User $actor): TimeEntry
    {
        return DB::transaction(function () use ($task, $employee, $actor) {
            $this->guardWorkable($task);
            $this->guardUnlocked($employee, now());

            $running = TimeEntry::query()
                ->where('employee_id', $employee->id)
                ->where('is_running', true)
                ->lockForUpdate()
                ->first();

            if ($running !== null) {
                throw ProductivityException::timerAlreadyRunning();
            }

            if ($task->status === TaskStatus::Accepted) {
                $this->workflow->transition($task, TaskStatus::InProgress, $actor);
            }

            $entry = TimeEntry::create([
                'tm_task_id' => $task->id,
                'employee_id' => $employee->id,
                'started_at' => now(),
                'ended_at' => null,
                'duration_seconds' => 0,
                'is_running' => true,
                'source' => TimeSource::Timer,
            ]);

            $this->attachToWeek($entry, $employee);

            return $entry;
        });
    }

    public function pause(Task $task, Employee $employee): TimeEntry
    {
        return $this->stopRunning($task, $employee);
    }

    public function stop(Task $task, Employee $employee): TimeEntry
    {
        return $this->stopRunning($task, $employee);
    }

    /**
     * @param  array{started_at: string, ended_at: string, note?: string|null, is_billable?: bool}  $attributes
     */
    public function logManual(Task $task, Employee $employee, array $attributes): TimeEntry
    {
        return DB::transaction(function () use ($task, $employee, $attributes) {
            $this->guardWorkable($task);

            $started = Carbon::parse($attributes['started_at']);
            $ended = Carbon::parse($attributes['ended_at']);

            $this->guardUnlocked($employee, $started);

            $entry = TimeEntry::create([
                'tm_task_id' => $task->id,
                'employee_id' => $employee->id,
                'started_at' => $started,
                'ended_at' => $ended,
                'duration_seconds' => max(0, $started->diffInSeconds($ended)),
                'is_running' => false,
                'source' => TimeSource::Manual,
                'note' => $attributes['note'] ?? null,
                'is_billable' => $attributes['is_billable'] ?? true,
            ]);

            $this->attachToWeek($entry, $employee);

            return $entry;
        });
    }

    public function discard(TimeEntry $entry): void
    {
        DB::transaction(function () use ($entry) {
            $timesheet = $entry->timesheet;

            if ($timesheet !== null && $timesheet->status->isLocked()) {
                throw ProductivityException::timesheetLocked();
            }

            $entry->delete();

            $timesheet?->refreshTotal();
        });
    }

    protected function stopRunning(Task $task, Employee $employee): TimeEntry
    {
        return DB::transaction(function () use ($task, $employee) {
            $entry = TimeEntry::query()
                ->where('tm_task_id', $task->id)
                ->where('employee_id', $employee->id)
                ->where('is_running', true)
                ->lockForUpdate()
                ->first();

            if ($entry === null) {
                throw ProductivityException::noRunningTimer();
            }

            $ended = now();

            $entry->update([
                'ended_at' => $ended,
                'duration_seconds' => max(0, $entry->started_at->diffInSeconds($ended)),
                'is_running' => false,
            ]);

            $entry->timesheet?->refreshTotal();

            return $entry->refresh();
        });
    }

    protected function guardWorkable(Task $task): void
    {
        if (! $task->status->isWorkable()) {
            throw ProductivityException::taskNotWorkable();
        }
    }

    protected function guardUnlocked(Employee $employee, Carbon $when): void
    {
        $week = WorkWeek::containing($when);

        $timesheet = Timesheet::query()
            ->where('employee_id', $employee->id)
            ->where('period_start', $week->start->toDateString())
            ->first();

        if ($timesheet !== null && $timesheet->status->isLocked()) {
            throw ProductivityException::timesheetLocked();
        }
    }

    protected function attachToWeek(TimeEntry $entry, Employee $employee): void
    {
        $week = WorkWeek::containing($entry->started_at);

        $timesheet = Timesheet::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'period_start' => $week->start->toDateString(),
            ],
            [
                'period_end' => $week->end->toDateString(),
                'status' => TimesheetStatus::Draft,
                'total_hours' => 0,
            ],
        );

        $entry->forceFill(['tm_timesheet_id' => $timesheet->id])->save();
        $timesheet->refreshTotal();
    }
}
