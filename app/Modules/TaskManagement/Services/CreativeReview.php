<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use App\Modules\TaskManagement\Enums\ReviewDecision;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreativeReview
{
    public function __construct(
        protected TaskWorkflow $workflow,
        protected TaskNotifier $notifier,
        protected DeliverableShareLinkService $shareLinks,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function submit(Task $task, Employee $employee, User $actor, array $files, ?string $notes = null): Deliverable
    {
        $deliverable = DB::transaction(function () use ($task, $employee, $actor, $files, $notes) {
            if (! in_array($task->status, [TaskStatus::InProgress, TaskStatus::Revision, TaskStatus::InReview], true)) {
                throw ProductivityException::cannotSubmitProof();
            }

            $task->deliverables()
                ->whereIn('status', [DeliverableStatus::Submitted, DeliverableStatus::InReview])
                ->update(['status' => DeliverableStatus::Superseded]);

            $version = (int) $task->deliverables()->max('version') + 1;

            $deliverable = Deliverable::create([
                'tm_task_id' => $task->id,
                'version' => $version,
                'submitted_by_employee_id' => $employee->id,
                'status' => DeliverableStatus::InReview,
                'notes' => $notes,
                'client_feedback' => null,
                'submitted_at' => now(),
            ]);

            foreach ($files as $file) {
                $deliverable->addMedia($file)->toMediaCollection('proofs');
            }

            if ($task->status !== TaskStatus::InReview) {
                $this->workflow->transition($task, TaskStatus::InReview, $actor);
            }

            return $deliverable->refresh();
        });

        $this->shareLinks->getOrCreate($deliverable->loadMissing('task'), $actor);

        $this->notifier->proofSubmitted($task->refresh(), $actor);

        return $deliverable->refresh();
    }

    public function decide(Deliverable $deliverable, User $reviewer, ReviewDecision $decision, ?string $comments = null): Deliverable
    {
        $deliverable = DB::transaction(function () use ($deliverable, $reviewer, $decision, $comments) {
            if (! $deliverable->status->isOpen()) {
                throw ProductivityException::deliverableNotOpen();
            }

            $latestOpen = $deliverable->task->deliverables()
                ->whereIn('status', [DeliverableStatus::Submitted, DeliverableStatus::InReview])
                ->orderByDesc('version')
                ->first();

            if ($latestOpen === null || $latestOpen->id !== $deliverable->id) {
                throw ProductivityException::deliverableNotOpen();
            }

            $round = (int) $deliverable->reviews()->max('round') + 1;

            $deliverable->reviews()->create([
                'reviewer_user_id' => $reviewer->id,
                'round' => $round,
                'decision' => $decision,
                'comments' => $comments,
                'reviewed_at' => now(),
            ]);

            $deliverable->update([
                'status' => $decision->resultingDeliverableStatus(),
            ]);

            if ($decision === ReviewDecision::Approve) {
                $this->shareLinks->getOrCreate($deliverable->refresh(), $reviewer);
            } else {
                $this->workflow->transition($deliverable->task, $decision->resultingTaskStatus(), $reviewer);
            }

            return $deliverable->refresh();
        });

        $deliverable->loadMissing('task');
        $this->notifier->proofDecided($deliverable->task, $reviewer, $decision);

        return $deliverable;
    }
}
