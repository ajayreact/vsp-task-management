<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\ContentCalendarStatusHistory;
use Illuminate\Support\Facades\DB;

class ContentCalendarStatusWorkflow
{
    /**
     * @var array<string, list<string>>
     */
    protected array $allowed = [
        'draft' => ['in_progress', 'ready', 'rejected'],
        'in_progress' => ['draft', 'ready', 'rejected'],
        'ready' => ['in_progress', 'under_review', 'draft', 'rejected'],
        'under_review' => ['approved', 'changes_requested', 'rejected'],
        'changes_requested' => ['ready', 'in_progress', 'rejected'],
        'approved' => ['scheduled', 'published', 'ready'],
        'scheduled' => ['published', 'approved'],
        'published' => [],
        'rejected' => ['draft', 'in_progress', 'ready'],
    ];

    public function recordInitial(ContentCalendarItem $item, ?User $actor = null, ?string $note = null): void
    {
        ContentCalendarStatusHistory::query()->create([
            'tm_content_calendar_item_id' => $item->id,
            'from_status' => null,
            'to_status' => $item->status->value,
            'note' => $note ?? 'Post created',
            'created_by_user_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }

    public function transition(
        ContentCalendarItem $item,
        ContentCalendarStatus $to,
        ?User $actor = null,
        ?string $note = null,
        bool $force = false,
    ): ContentCalendarItem {
        return DB::transaction(function () use ($item, $to, $actor, $note, $force) {
            /** @var ContentCalendarItem $locked */
            $locked = ContentCalendarItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if ($from === $to) {
                return $locked;
            }

            if (! $force && ! $this->canTransition($from, $to)) {
                throw ProductivityException::invalidTransition(
                    sprintf('Cannot change content status from %s to %s.', $from->label(), $to->label()),
                );
            }

            $locked->status = $to;

            if ($to === ContentCalendarStatus::UnderReview) {
                $locked->reviewed_at = now();
            }

            if ($to === ContentCalendarStatus::Published && $locked->published_at === null) {
                $locked->published_at = now();
            }

            $locked->updated_by_user_id = $actor?->id ?? $locked->updated_by_user_id;
            $locked->save();

            ContentCalendarStatusHistory::query()->create([
                'tm_content_calendar_item_id' => $locked->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'note' => $note,
                'created_by_user_id' => $actor?->id,
                'created_at' => now(),
            ]);

            return $locked->refresh();
        });
    }

    public function markReadyAfterUpload(ContentCalendarItem $item, User $actor): ContentCalendarItem
    {
        if (! in_array($item->status, [ContentCalendarStatus::Draft, ContentCalendarStatus::InProgress], true)) {
            return $item;
        }

        return $this->transition(
            $item,
            ContentCalendarStatus::Ready,
            $actor,
            'Creative uploaded — Post Ready',
        );
    }

    public function canTransition(ContentCalendarStatus $from, ContentCalendarStatus $to): bool
    {
        return in_array($to->value, $this->allowed[$from->value] ?? [], true);
    }
}
