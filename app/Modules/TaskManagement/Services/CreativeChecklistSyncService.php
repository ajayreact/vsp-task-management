<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskChecklistItem;
use App\Modules\TaskManagement\Support\CreativeChecklistDefaults;
use Illuminate\Support\Facades\DB;

class CreativeChecklistSyncService
{
    /**
     * Add any missing default checklist items for the task's creative type.
     * Never deletes existing items (including completed history).
     */
    public function syncDefaults(Task $task): void
    {
        $templates = CreativeChecklistDefaults::for($task->creative_type);

        if ($templates === []) {
            return;
        }

        DB::transaction(function () use ($task, $templates) {
            /** @var Task $locked */
            $locked = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            $existing = $locked->checklistItems()->get();
            $keys = $existing->pluck('template_key')->filter()->all();
            $titles = $existing->pluck('title')->all();
            $nextOrder = (int) $existing->max('sort_order');

            foreach ($templates as $template) {
                if (in_array($template['key'], $keys, true)) {
                    continue;
                }

                $titleMatch = $existing->first(
                    fn (TaskChecklistItem $item) => $item->title === $template['title'] && $item->template_key === null,
                );

                if ($titleMatch !== null) {
                    $titleMatch->update([
                        'source' => CreativeChecklistDefaults::SOURCE_SYSTEM,
                        'template_key' => $template['key'],
                        'checklist_group' => $template['group'],
                        'is_mandatory' => true,
                    ]);
                    $keys[] = $template['key'];

                    continue;
                }

                if (in_array($template['title'], $titles, true)) {
                    continue;
                }

                $nextOrder++;

                $locked->checklistItems()->create([
                    'title' => $template['title'],
                    'source' => CreativeChecklistDefaults::SOURCE_SYSTEM,
                    'template_key' => $template['key'],
                    'checklist_group' => $template['group'],
                    'is_mandatory' => true,
                    'sort_order' => $nextOrder,
                ]);

                $keys[] = $template['key'];
                $titles[] = $template['title'];
            }
        });
    }

    public function hasIncompleteRequired(Task $task): bool
    {
        $requiredKeys = CreativeChecklistDefaults::requiredKeysFor($task->creative_type);

        if ($requiredKeys === []) {
            return false;
        }

        return $task->checklistItems()
            ->whereIn('template_key', $requiredKeys)
            ->where('is_completed', false)
            ->exists();
    }

    public function assertReadyForReview(Task $task): void
    {
        if ($this->hasIncompleteRequired($task)) {
            throw ProductivityException::checklistIncompleteForReady();
        }
    }
}
