<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Enums\ReviewDecision;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Http\Requests\DeliverableProofRequest;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\CreativeReview;
use App\Modules\TaskManagement\Services\DeliverableShareLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliverableController extends Controller
{
    public function __construct(protected CreativeReview $review) {}

    public function store(DeliverableProofRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('submitProof', $task);

        $validated = $request->validated();

        $employee = $request->user()->employee;
        abort_if($employee === null, 403);

        try {
            $this->review->submit(
                $task,
                $employee,
                $request->user(),
                $validated['files'],
                $validated['notes'] ?? null,
            );
        } catch (ProductivityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Proof submitted for review.');
    }

    public function review(Request $request, Deliverable $deliverable): RedirectResponse
    {
        $this->authorize('reviewProof', $deliverable->task);

        $validated = $request->validate([
            'decision' => ['required', Rule::enum(ReviewDecision::class)],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->review->decide(
                $deliverable,
                $request->user(),
                ReviewDecision::from($validated['decision']),
                $validated['comments'] ?? null,
            );
        } catch (ProductivityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Review recorded.');
    }

    public function shareLink(Request $request, Deliverable $deliverable, DeliverableShareLinkService $links): RedirectResponse
    {
        $this->authorize('share', $deliverable);

        $user = $request->user();
        abort_if($user === null, 403);

        $deliverable->loadMissing('task');
        $link = $links->getOrCreate($deliverable, $user);
        $shareUrl = $link->publicUrl();

        return back()->with([
            'share_url' => $shareUrl,
            'share_message' => self::buildShareMessage($deliverable->task, $shareUrl),
        ]);
    }

    public static function buildShareMessage(Task $task, string $shareUrl): string
    {
        return trim($task->title)."\n".$shareUrl;
    }
}
