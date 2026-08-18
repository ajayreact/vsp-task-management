<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Enums\RecurrenceFrequency;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskRecurrenceRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskRecurrenceController extends Controller
{
    public function upsert(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manageRecurrence', $task);
        abort_if($task->recurrence_occurrence_number !== null && $task->recurrence_occurrence_number > 0, 422, 'Recurrence can only be configured on the source task.');

        $validated = $request->validate([
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'max_occurrences' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $rule = TaskRecurrenceRule::query()->updateOrCreate(
            ['source_tm_task_id' => $task->id],
            [
                'created_by_user_id' => $request->user()->id,
                'frequency' => $validated['frequency'],
                'interval' => (int) $validated['interval'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'max_occurrences' => $validated['max_occurrences'] ?? null,
                'is_active' => (bool) $validated['is_active'],
            ],
        );

        $task->update([
            'tm_recurrence_rule_id' => $rule->id,
            'recurrence_occurrence_number' => 0,
        ]);

        return back()->with('success', 'Recurrence settings saved.');
    }
}
