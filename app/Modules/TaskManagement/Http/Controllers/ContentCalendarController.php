<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use App\Modules\TaskManagement\Http\Requests\ContentCalendarItemRequest;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Services\ContentCalendarItemShareLinkService;
use App\Modules\TaskManagement\Services\ContentCalendarScheduleShareLinkService;
use App\Modules\TaskManagement\Services\MediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ContentCalendarController extends Controller
{
    public function __construct(
        protected ContentCalendarItemShareLinkService $itemShareLinks,
        protected ContentCalendarScheduleShareLinkService $scheduleShareLinks,
        protected MediaStorageService $mediaStorage,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContentCalendarItem::class);

        $clientId = $request->integer('client') ?: Company::query()->orderBy('name')->value('id');
        $period = $this->resolvePeriod($request);
        $search = trim((string) $request->string('search'));
        $contentType = $request->string('content_type')->value() ?: null;
        $platform = $request->string('platform')->value() ?: null;
        $status = $request->string('status')->value() ?: null;

        $items = ContentCalendarItem::query()
            ->with(['company:id,name', 'createdBy:id,name', 'media'])
            ->when($clientId !== null, fn ($query) => $query->where('tm_company_id', $clientId))
            ->whereDate('scheduled_date', '>=', $period['start'])
            ->whereDate('scheduled_date', '<=', $period['end'])
            ->when($contentType !== null && $contentType !== '', fn ($query) => $query->where('content_type', $contentType))
            ->when($platform !== null && $platform !== '', fn ($query) => $query->where('platform', $platform))
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('description', 'like', "%{$search}%"))
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->get()
            ->map(fn (ContentCalendarItem $item) => $this->rowPayload($item, $request));

        return Inertia::render('TaskManagement/content-calendar/index', [
            'items' => $items,
            'clients' => Company::query()->orderBy('name')->get(['id', 'name']),
            'contentTypes' => ContentCalendarType::options(),
            'platforms' => ContentCalendarPlatform::options(),
            'statuses' => ContentCalendarStatus::options(),
            'period' => $period,
            'filters' => [
                'client' => $clientId,
                'search' => $search !== '' ? $search : null,
                'content_type' => $contentType,
                'platform' => $platform,
                'status' => $status,
            ],
            'can' => [
                'manage' => $request->user()->can('create', ContentCalendarItem::class),
                'share' => $request->user()->can(Ability::ShareContentCalendar->value),
            ],
        ]);
    }

    public function store(ContentCalendarItemRequest $request): RedirectResponse
    {
        $this->authorize('create', ContentCalendarItem::class);

        $validated = $request->validated();
        $files = $request->file('files', []) ?? [];

        unset($validated['files']);

        $item = ContentCalendarItem::query()->create([
            ...$validated,
            'created_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
        ]);

        foreach ($files as $file) {
            $item->addMedia($file)
                ->withCustomProperties(['uploaded_by_user_id' => $request->user()->id])
                ->toMediaCollection('attachments');
        }

        return back()->with('success', 'Content scheduled.');
    }

    public function update(ContentCalendarItemRequest $request, ContentCalendarItem $calendarItem): RedirectResponse
    {
        $this->authorize('update', $calendarItem);

        $validated = $request->validated();
        $files = $request->file('files', []) ?? [];

        unset($validated['files']);

        $calendarItem->update([
            ...$validated,
            'updated_by_user_id' => $request->user()->id,
        ]);

        foreach ($files as $file) {
            $calendarItem->addMedia($file)
                ->withCustomProperties(['uploaded_by_user_id' => $request->user()->id])
                ->toMediaCollection('attachments');
        }

        return back()->with('success', 'Content updated.');
    }

    public function destroy(ContentCalendarItem $calendarItem): RedirectResponse
    {
        $this->authorize('delete', $calendarItem);

        foreach ($calendarItem->getMedia('attachments') as $media) {
            $this->mediaStorage->deleteMedia($media, 'manual_content_calendar_delete', allowPermanent: true);
        }

        $calendarItem->delete();

        return back()->with('success', 'Content removed.');
    }

    public function previewAttachment(Request $request, ContentCalendarItem $calendarItem, Media $media): SymfonyResponse
    {
        $this->authorize('view', $calendarItem);
        $this->assertAttachmentMedia($calendarItem, $media);

        if (! is_file($media->getPath())) {
            abort(404);
        }

        return $media->toInlineResponse($request);
    }

    public function downloadAttachment(Request $request, ContentCalendarItem $calendarItem, Media $media): SymfonyResponse
    {
        $this->authorize('view', $calendarItem);
        $this->assertAttachmentMedia($calendarItem, $media);

        if (! is_file($media->getPath())) {
            abort(404);
        }

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    public function shareItem(Request $request, ContentCalendarItem $calendarItem): RedirectResponse
    {
        $this->authorize('share', $calendarItem);

        $link = $this->itemShareLinks->getOrCreate($calendarItem, $request->user());

        return back()->with('success', 'Share link ready.')->with('share_url', $link->publicUrl());
    }

    public function shareSchedule(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', ContentCalendarItem::class);
        abort_unless($request->user()->can(Ability::ShareContentCalendar->value) || $request->user()->can('create', ContentCalendarItem::class), 403);

        $client = Company::query()->findOrFail($request->integer('client'));
        $period = $this->resolvePeriod($request);

        $link = $this->scheduleShareLinks->getOrCreate(
            $client,
            Carbon::parse($period['start']),
            Carbon::parse($period['end']),
            $request->user(),
        );

        return back()->with('success', 'Schedule share link ready.')->with('share_url', $link->publicUrl());
    }

    /**
     * @return array{start: string, end: string, label: string, previous_start: string, next_start: string}
     */
    protected function resolvePeriod(Request $request): array
    {
        if ($request->filled('period_start')) {
            $start = Carbon::parse((string) $request->string('period_start'))->startOfDay();
        } else {
            $today = now()->startOfDay();
            $start = $today->day <= 15
                ? $today->copy()->startOfMonth()
                : $today->copy()->startOfMonth()->addDays(15);
        }

        $end = $start->copy()->addDays(14);

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'label' => $start->format('M j').' – '.$end->format('M j, Y'),
            'previous_start' => $start->copy()->subDays(15)->toDateString(),
            'next_start' => $start->copy()->addDays(15)->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowPayload(ContentCalendarItem $item, Request $request): array
    {
        $attachments = $item->getMedia('attachments')->map(function (Media $media) use ($item): array {
            return [
                'uuid' => $media->uuid,
                'name' => $media->file_name,
                'mime' => $media->mime_type,
                'size' => $media->size,
                'preview_url' => route('tasks.content-calendar.attachments.preview', [
                    'calendarItem' => $item,
                    'media' => $media->uuid,
                ]),
                'download_url' => route('tasks.content-calendar.attachments.download', [
                    'calendarItem' => $item,
                    'media' => $media->uuid,
                ]),
                'can_preview' => $this->canPreview($media),
            ];
        })->values()->all();

        return [
            'id' => $item->id,
            'scheduled_date' => $item->scheduled_date->toDateString(),
            'scheduled_day' => $item->scheduled_date->format('D'),
            'scheduled_time' => $item->scheduled_time,
            'content_type' => $item->content_type->value,
            'content_type_label' => $item->content_type->label(),
            'platform' => $item->platform->value,
            'platform_label' => $item->platform->label(),
            'description' => $item->description,
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'internal_notes' => $item->internal_notes,
            'uploaded_by' => $item->createdBy->name,
            'attachments' => $attachments,
            'can' => [
                'update' => $request->user()->can('update', $item),
                'delete' => $request->user()->can('delete', $item),
                'share' => $request->user()->can('share', $item),
            ],
        ];
    }

    protected function assertAttachmentMedia(ContentCalendarItem $item, Media $media): void
    {
        abort_unless(
            $media->collection_name === 'attachments'
                && $media->model_type === $item->getMorphClass()
                && (int) $media->model_id === (int) $item->id,
            404,
        );
    }

    protected function canPreview(Media $media): bool
    {
        return str_starts_with($media->mime_type, 'image/')
            || str_starts_with($media->mime_type, 'video/')
            || $media->mime_type === 'application/pdf';
    }
}
