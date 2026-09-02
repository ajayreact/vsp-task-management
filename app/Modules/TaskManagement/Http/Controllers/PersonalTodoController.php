<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Enums\PersonalTodoStatus;
use App\Modules\TaskManagement\Http\Requests\PersonalTodoRequest;
use App\Modules\TaskManagement\Models\PersonalTodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class PersonalTodoController extends Controller
{
    public function store(PersonalTodoRequest $request): RedirectResponse
    {
        $this->authorize('create', PersonalTodo::class);

        PersonalTodo::query()->create($this->attributes($request));

        return back()->with('success', 'Todo added.');
    }

    public function update(PersonalTodoRequest $request, PersonalTodo $personalTodo): RedirectResponse
    {
        $this->authorize('update', $personalTodo);

        $personalTodo->update($this->attributes($request, $personalTodo));

        return back()->with('success', 'Todo updated.');
    }

    public function complete(PersonalTodo $personalTodo): RedirectResponse
    {
        $this->authorize('update', $personalTodo);

        $personalTodo->update([
            'status' => PersonalTodoStatus::Completed,
            'completed_at' => now(),
        ]);

        return back();
    }

    public function reopen(PersonalTodo $personalTodo): RedirectResponse
    {
        $this->authorize('update', $personalTodo);

        $personalTodo->update([
            'status' => PersonalTodoStatus::Pending,
            'completed_at' => null,
        ]);

        return back();
    }

    public function moveToToday(PersonalTodo $personalTodo): RedirectResponse
    {
        $this->authorize('update', $personalTodo);

        $personalTodo->update([
            'due_date' => today(),
            'reminder_at' => $this->reminderAt(
                today(),
                $personalTodo->due_time,
                $this->minutesBefore($personalTodo),
            ),
            'reminder_sent_at' => null,
        ]);

        return back()->with('success', 'Todo moved to today.');
    }

    public function destroy(PersonalTodo $personalTodo): RedirectResponse
    {
        $this->authorize('delete', $personalTodo);

        $personalTodo->delete();

        return back()->with('success', 'Todo deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(PersonalTodoRequest $request, ?PersonalTodo $existing = null): array
    {
        $dueDate = $request->input('due_date');
        $dueTime = $request->input('due_time');
        $minutesBefore = $request->has('reminder_minutes_before')
            ? ($request->integer('reminder_minutes_before') ?: null)
            : ($existing ? $this->minutesBefore($existing) : null);

        return [
            'user_id' => $request->user()->id,
            'title' => $request->string('title')->trim()->value(),
            'note' => $request->input('note'),
            'due_date' => $dueDate,
            'due_time' => $dueTime,
            'priority' => $request->input('priority'),
            'reminder_at' => $this->reminderAt(
                $dueDate ? Carbon::parse($dueDate) : null,
                $dueTime,
                $minutesBefore,
            ),
            'reminder_sent_at' => null,
        ];
    }

    protected function reminderAt(?Carbon $dueDate, ?string $dueTime, ?int $minutesBefore): ?Carbon
    {
        if ($dueDate === null || $minutesBefore === null) {
            return null;
        }

        $due = $dueDate->copy()->startOfDay();

        if ($dueTime !== null) {
            $parts = explode(':', $dueTime);
            $due->setTime((int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0));
        } else {
            $due->endOfDay();
        }

        return $due->copy()->subMinutes($minutesBefore);
    }

    protected function minutesBefore(PersonalTodo $todo): ?int
    {
        if ($todo->reminder_at === null || $todo->effectiveDueAt() === null) {
            return null;
        }

        return (int) $todo->effectiveDueAt()->diffInMinutes($todo->reminder_at, absolute: true);
    }
}
