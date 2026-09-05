<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\ContentCalendarItemShareLink;
use Illuminate\Support\Facades\DB;

class ClientContentCalendarReview
{
    public function __construct(
        protected ContentCalendarStatusWorkflow $workflow,
    ) {}

    public function approve(ContentCalendarItemShareLink $link): void
    {
        DB::transaction(function () use ($link) {
            $item = $link->item()->lockForUpdate()->firstOrFail();
            $this->guardCanRespond($item);

            $this->workflow->transition(
                $item,
                ContentCalendarStatus::Approved,
                null,
                'Client approved',
            );

            $item->refresh();
            $item->update([
                'client_feedback' => null,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function requestChanges(ContentCalendarItemShareLink $link, ?string $feedback): void
    {
        DB::transaction(function () use ($link, $feedback) {
            $item = $link->item()->lockForUpdate()->firstOrFail();
            $this->guardCanRespond($item);

            $this->workflow->transition(
                $item,
                ContentCalendarStatus::ChangesRequested,
                null,
                $feedback ? 'Client requested changes: '.$feedback : 'Client requested changes',
            );

            $item->refresh();
            $item->update([
                'client_feedback' => $feedback,
                'reviewed_at' => now(),
            ]);
        });
    }

    protected function guardCanRespond(ContentCalendarItem $item): void
    {
        if ($item->status !== ContentCalendarStatus::UnderReview) {
            throw ProductivityException::contentClientReviewUnavailable();
        }
    }
}
