<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\DeliverableShareLink;
use Illuminate\Support\Facades\DB;

class ClientDeliverableReview
{
    public function __construct(
        protected TaskWorkflow $workflow,
        protected TaskNotifier $notifier,
    ) {}

    public function approve(DeliverableShareLink $link): void
    {
        DB::transaction(function () use ($link) {
            $link->loadMissing('createdBy');
            $deliverable = $link->deliverable()->lockForUpdate()->firstOrFail();
            $task = $deliverable->task()->lockForUpdate()->firstOrFail();

            $this->guardClientCanRespond($deliverable, $task);

            $actor = $link->createdBy ?? User::query()->findOrFail($link->created_by_user_id);

            $this->workflow->completeAfterClientApproval($task, $actor);
        });

        $task = $link->deliverable->task->refresh();
        $this->notifier->clientApproved($task);
    }

    public function requestChanges(DeliverableShareLink $link, ?string $feedback): void
    {
        DB::transaction(function () use ($link, $feedback) {
            $link->loadMissing('createdBy');
            $deliverable = $link->deliverable()->lockForUpdate()->firstOrFail();
            $task = $deliverable->task()->lockForUpdate()->firstOrFail();

            $this->guardClientCanRespond($deliverable, $task);

            $deliverable->update([
                'status' => DeliverableStatus::ChangesRequested,
                'client_feedback' => $feedback,
            ]);

            $actor = $link->createdBy ?? User::query()->findOrFail($link->created_by_user_id);

            $this->workflow->transition($task, TaskStatus::Revision, $actor);
        });

        $task = $link->deliverable->task->refresh();
        $this->notifier->clientRequestedChanges($task, $feedback);
    }

    protected function guardClientCanRespond($deliverable, $task): void
    {
        if ($deliverable->status !== DeliverableStatus::Approved) {
            throw ProductivityException::clientReviewUnavailable();
        }

        if ($task->status !== TaskStatus::InReview) {
            throw ProductivityException::clientReviewUnavailable();
        }
    }
}
