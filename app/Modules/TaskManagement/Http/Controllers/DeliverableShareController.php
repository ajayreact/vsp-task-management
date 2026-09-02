<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\DeliverableShareLink;
use App\Modules\TaskManagement\Services\ClientDeliverableReview;
use App\Modules\TaskManagement\Services\DeliverableShareLinkService;
use App\Modules\TaskManagement\Services\DeliverableShareResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class DeliverableShareController extends Controller
{
    public function __construct(
        protected ClientDeliverableReview $clientReview,
        protected DeliverableShareLinkService $shareLinks,
        protected DeliverableShareResponder $responder,
    ) {}

    public function show(string $token): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderShow($this->shareLinks->resolveByToken($token), preferLegacyUrls: true),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function showShort(string $shortCode): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderShow($this->shareLinks->resolveByShortCode($shortCode)),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    public function approve(string $token): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->runClientAction(
                fn () => $this->shareLinks->resolveByToken($token),
                fn (DeliverableShareLink $link) => $this->clientReview->approve($link),
                'Thank you. Your approval has been recorded.',
            ),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function approveShort(string $shortCode): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->runClientAction(
                fn () => $this->shareLinks->resolveByShortCode($shortCode),
                fn (DeliverableShareLink $link) => $this->clientReview->approve($link),
                'Thank you. Your approval has been recorded.',
            ),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    public function requestChanges(Request $request, string $token): SymfonyResponse
    {
        $validated = $request->validate([
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->handleShareRequest(
            fn () => $this->runClientAction(
                fn () => $this->shareLinks->resolveByToken($token),
                fn (DeliverableShareLink $link) => $this->clientReview->requestChanges($link, $validated['feedback'] ?? null),
                'Your feedback has been sent to the team.',
            ),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function requestChangesShort(Request $request, string $shortCode): SymfonyResponse
    {
        $validated = $request->validate([
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->handleShareRequest(
            fn () => $this->runClientAction(
                fn () => $this->shareLinks->resolveByShortCode($shortCode),
                fn (DeliverableShareLink $link) => $this->clientReview->requestChanges($link, $validated['feedback'] ?? null),
                'Your feedback has been sent to the team.',
            ),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    public function file(string $token, string $mediaUuid): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderFile($this->shareLinks->resolveByToken($token), $mediaUuid),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8), 'media_uuid' => $mediaUuid],
        );
    }

    public function fileShort(string $shortCode, string $mediaUuid): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderFile($this->shareLinks->resolveByShortCode($shortCode), $mediaUuid),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode, 'media_uuid' => $mediaUuid],
        );
    }

    public function downloadFile(string $token, string $mediaUuid): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderDownloadFile($this->shareLinks->resolveByToken($token), $mediaUuid),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8), 'media_uuid' => $mediaUuid],
        );
    }

    public function downloadFileShort(string $shortCode, string $mediaUuid): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderDownloadFile($this->shareLinks->resolveByShortCode($shortCode), $mediaUuid),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode, 'media_uuid' => $mediaUuid],
        );
    }

    /**
     * @param  callable(): Response|RedirectResponse|SymfonyResponse  $callback
     * @param  array<string, mixed>  $context
     */
    protected function handleShareRequest(callable $callback, array $context = []): SymfonyResponse|JsonResponse
    {
        try {
            $response = $callback();

            return $response instanceof SymfonyResponse
                ? $response
                : $response->toResponse(request());
        } catch (DeliverableShareException $exception) {
            return $this->responder->respond($exception, request(), $context);
        } catch (Throwable $throwable) {
            return $this->responder->respondUnexpected($throwable, request(), $context);
        }
    }

    protected function renderShow(DeliverableShareLink $link, bool $preferLegacyUrls = false): InertiaResponse
    {
        $deliverable = $link->deliverable;
        $task = $deliverable->task;

        return Inertia::render('TaskManagement/share/show', [
            'brand' => config('app.name'),
            'client_name' => $task->project->company->name,
            'project_name' => $task->project->name,
            'task_title' => $task->title,
            'deliverable' => [
                'title' => 'Version '.$deliverable->version,
                'status' => $deliverable->status->label(),
                'submitted_at' => $deliverable->submitted_at->toDateString(),
            ],
            'files' => $this->publicProofFiles($link, $preferLegacyUrls),
            'can_respond' => $deliverable->status->value === 'approved' && $task->status->value === 'in_review',
            'approve_url' => $preferLegacyUrls
                ? route('share.approve', ['token' => $link->token])
                : $link->publicApproveUrl(),
            'request_changes_url' => $preferLegacyUrls
                ? route('share.request-changes', ['token' => $link->token])
                : $link->publicRequestChangesUrl(),
        ]);
    }

    protected function renderFile(DeliverableShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->proofMediaForLink($link, $mediaUuid);

        if (! is_file($media->getPath())) {
            throw DeliverableShareException::notFound([
                'share_link_id' => $link->id,
                'deliverable_id' => $link->tm_deliverable_id,
                'missing' => 'file',
            ]);
        }

        return $media->toInlineResponse(request());
    }

    protected function renderDownloadFile(DeliverableShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->proofMediaForLink($link, $mediaUuid);

        if (! is_file($media->getPath())) {
            throw DeliverableShareException::notFound([
                'share_link_id' => $link->id,
                'deliverable_id' => $link->tm_deliverable_id,
                'missing' => 'file',
            ]);
        }

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    /**
     * @param  callable(): DeliverableShareLink  $resolveLink
     * @param  callable(DeliverableShareLink): void  $action
     */
    protected function runClientAction(callable $resolveLink, callable $action, string $success): RedirectResponse
    {
        $link = $resolveLink();

        try {
            $action($link);
        } catch (ProductivityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }

    /**
     * @return list<array{name: string, mime: string, size: int, url: string, download_url: string}>
     */
    protected function publicProofFiles(DeliverableShareLink $link, bool $preferLegacyUrls = false): array
    {
        return $link->deliverable
            ->getMedia('proofs')
            ->map(fn (Media $media) => [
                'name' => $media->file_name,
                'mime' => $media->mime_type,
                'size' => $media->size,
                'url' => $preferLegacyUrls
                    ? route('share.file', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                    : $link->publicFileUrl($media->uuid),
                'download_url' => $preferLegacyUrls
                    ? route('share.file.download', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                    : $link->publicFileDownloadUrl($media->uuid),
            ])
            ->values()
            ->all();
    }

    protected function proofMediaForLink(DeliverableShareLink $link, string $mediaUuid): Media
    {
        $media = Media::query()->where('uuid', $mediaUuid)->first();

        if ($media === null) {
            throw DeliverableShareException::notFound([
                'share_link_id' => $link->id,
                'deliverable_id' => $link->tm_deliverable_id,
                'missing' => 'media',
            ]);
        }

        $deliverableClass = (new \App\Modules\TaskManagement\Models\Deliverable)->getMorphClass();

        if ($media->collection_name !== 'proofs'
            || $media->model_type !== $deliverableClass
            || (int) $media->model_id !== (int) $link->tm_deliverable_id) {
            throw DeliverableShareException::unauthorized([
                'share_link_id' => $link->id,
                'deliverable_id' => $link->tm_deliverable_id,
                'media_uuid' => $mediaUuid,
            ]);
        }

        return $media;
    }
}
