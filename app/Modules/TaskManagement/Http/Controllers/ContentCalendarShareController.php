<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\ContentCalendarItemShareLink;
use App\Modules\TaskManagement\Models\ContentCalendarScheduleShareLink;
use App\Modules\TaskManagement\Services\ContentCalendarItemShareLinkService;
use App\Modules\TaskManagement\Services\ContentCalendarScheduleShareLinkService;
use App\Modules\TaskManagement\Services\DeliverableShareResponder;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ContentCalendarShareController extends Controller
{
    public function __construct(
        protected ContentCalendarItemShareLinkService $itemShareLinks,
        protected ContentCalendarScheduleShareLinkService $scheduleShareLinks,
        protected DeliverableShareResponder $responder,
    ) {}

    public function showItem(string $token): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderItemShow($this->itemShareLinks->resolveByToken($token), preferLegacyUrls: true),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function showItemShort(string $shortCode): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderItemShow($this->itemShareLinks->resolveByShortCode($shortCode)),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    public function showSchedule(string $token): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderScheduleShow($this->scheduleShareLinks->resolveByToken($token), preferLegacyUrls: true),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function showScheduleShort(string $shortCode): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderScheduleShow($this->scheduleShareLinks->resolveByShortCode($shortCode)),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    public function fileItem(string $token, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderItemFile($this->itemShareLinks->resolveByToken($token), $mediaUuid),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8), 'media_uuid' => $mediaUuid],
        );
    }

    public function fileItemShort(string $shortCode, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderItemFile($this->itemShareLinks->resolveByShortCode($shortCode), $mediaUuid),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode, 'media_uuid' => $mediaUuid],
        );
    }

    public function downloadItemFile(string $token, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderItemDownload($this->itemShareLinks->resolveByToken($token), $mediaUuid),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8), 'media_uuid' => $mediaUuid],
        );
    }

    public function downloadItemFileShort(string $shortCode, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderItemDownload($this->itemShareLinks->resolveByShortCode($shortCode), $mediaUuid),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode, 'media_uuid' => $mediaUuid],
        );
    }

    public function fileSchedule(string $token, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderScheduleFile($this->scheduleShareLinks->resolveByToken($token), $mediaUuid),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8), 'media_uuid' => $mediaUuid],
        );
    }

    public function fileScheduleShort(string $shortCode, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderScheduleFile($this->scheduleShareLinks->resolveByShortCode($shortCode), $mediaUuid),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode, 'media_uuid' => $mediaUuid],
        );
    }

    public function downloadScheduleFile(string $token, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderScheduleDownload($this->scheduleShareLinks->resolveByToken($token), $mediaUuid),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8), 'media_uuid' => $mediaUuid],
        );
    }

    public function downloadScheduleFileShort(string $shortCode, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderScheduleDownload($this->scheduleShareLinks->resolveByShortCode($shortCode), $mediaUuid),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode, 'media_uuid' => $mediaUuid],
        );
    }

    /**
     * @param  callable(): InertiaResponse|SymfonyResponse  $callback
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

    protected function renderItemShow(ContentCalendarItemShareLink $link, bool $preferLegacyUrls = false): InertiaResponse
    {
        $item = $link->item;
        $item->loadMissing('company', 'media');

        return Inertia::render('TaskManagement/content-share/show-item', [
            'brand' => config('app.name'),
            'client_name' => $item->company->name,
            'item' => $this->publicItemPayload($item, $link, $preferLegacyUrls),
        ]);
    }

    protected function renderScheduleShow(ContentCalendarScheduleShareLink $link, bool $preferLegacyUrls = false): InertiaResponse
    {
        $link->loadMissing('company');

        $items = ContentCalendarItem::query()
            ->with('media')
            ->where('tm_company_id', $link->tm_company_id)
            ->whereDate('scheduled_date', '>=', $link->period_start)
            ->whereDate('scheduled_date', '<=', $link->period_end)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get()
            ->map(fn (ContentCalendarItem $item) => $this->publicItemPayload($item, $link, $preferLegacyUrls));

        return Inertia::render('TaskManagement/content-share/show-schedule', [
            'brand' => config('app.name'),
            'client_name' => $link->company->name,
            'period_label' => $link->period_start->format('M j').' – '.$link->period_end->format('M j, Y'),
            'items' => $items,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function publicItemPayload(ContentCalendarItem $item, ContentCalendarItemShareLink|ContentCalendarScheduleShareLink $link, bool $preferLegacyUrls): array
    {
        $attachments = $item->getMedia('attachments')->map(function (Media $media) use ($link, $preferLegacyUrls): array {
            $previewUrl = $link instanceof ContentCalendarItemShareLink
                ? ($preferLegacyUrls
                    ? route('content-share.file', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                    : $link->publicFileUrl($media->uuid))
                : ($preferLegacyUrls
                    ? route('content-schedule-share.file', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                    : $link->publicFileUrl($media->uuid));

            $downloadUrl = $link instanceof ContentCalendarItemShareLink
                ? ($preferLegacyUrls
                    ? route('content-share.file.download', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                    : $link->publicFileDownloadUrl($media->uuid))
                : ($preferLegacyUrls
                    ? route('content-schedule-share.file.download', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                    : $link->publicFileDownloadUrl($media->uuid));

            return [
                'name' => $media->file_name,
                'mime' => $media->mime_type,
                'preview_url' => $previewUrl,
                'download_url' => $downloadUrl,
                'can_preview' => str_starts_with($media->mime_type, 'image/')
                    || str_starts_with($media->mime_type, 'video/')
                    || $media->mime_type === 'application/pdf',
            ];
        })->values()->all();

        return [
            'scheduled_date' => $item->scheduled_date->format('M j, Y'),
            'scheduled_time' => $item->scheduled_time,
            'content_type' => $item->content_type->label(),
            'platform' => $item->platform->label(),
            'description' => $item->description,
            'attachments' => $attachments,
        ];
    }

    protected function renderItemFile(ContentCalendarItemShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->itemMediaForLink($link, $mediaUuid);

        return $media->toInlineResponse(request());
    }

    protected function renderItemDownload(ContentCalendarItemShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->itemMediaForLink($link, $mediaUuid);

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    protected function renderScheduleFile(ContentCalendarScheduleShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->scheduleMediaForLink($link, $mediaUuid);

        return $media->toInlineResponse(request());
    }

    protected function renderScheduleDownload(ContentCalendarScheduleShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->scheduleMediaForLink($link, $mediaUuid);

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    protected function itemMediaForLink(ContentCalendarItemShareLink $link, string $mediaUuid): Media
    {
        $media = Media::query()->where('uuid', $mediaUuid)->first();

        if ($media === null) {
            throw DeliverableShareException::notFound(['missing' => 'media']);
        }

        if ($media->collection_name !== 'attachments'
            || $media->model_type !== $link->item->getMorphClass()
            || (int) $media->model_id !== (int) $link->tm_content_calendar_item_id) {
            throw DeliverableShareException::unauthorized(['media_uuid' => $mediaUuid]);
        }

        if (! is_file($media->getPath())) {
            throw DeliverableShareException::notFound(['missing' => 'file']);
        }

        return $media;
    }

    protected function scheduleMediaForLink(ContentCalendarScheduleShareLink $link, string $mediaUuid): Media
    {
        $media = Media::query()->where('uuid', $mediaUuid)->first();

        if ($media === null) {
            throw DeliverableShareException::notFound(['missing' => 'media']);
        }

        if ($media->collection_name !== 'attachments' || $media->model_type !== (new ContentCalendarItem)->getMorphClass()) {
            throw DeliverableShareException::unauthorized(['media_uuid' => $mediaUuid]);
        }

        $item = ContentCalendarItem::query()->find((int) $media->model_id);

        if ($item === null
            || (int) $item->tm_company_id !== (int) $link->tm_company_id
            || $item->scheduled_date->lt($link->period_start)
            || $item->scheduled_date->gt($link->period_end)) {
            throw DeliverableShareException::unauthorized(['media_uuid' => $mediaUuid]);
        }

        if (! is_file($media->getPath())) {
            throw DeliverableShareException::notFound(['missing' => 'file']);
        }

        return $media;
    }
}
