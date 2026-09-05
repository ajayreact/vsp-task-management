<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarStatus;
use App\Modules\TaskManagement\Enums\ContentCalendarTopic;
use App\Modules\TaskManagement\Enums\ContentCalendarType;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Http\Requests\ContentCalendarItemRequest;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\Holiday;
use App\Modules\TaskManagement\Services\ContentCalendarHolidayService;
use App\Modules\TaskManagement\Services\ContentCalendarImporter;
use App\Modules\TaskManagement\Services\ContentCalendarItemShareLinkService;
use App\Modules\TaskManagement\Services\ContentCalendarScheduleShareLinkService;
use App\Modules\TaskManagement\Services\ContentCalendarStatusWorkflow;
use App\Modules\TaskManagement\Services\MediaStorageService;
use App\Modules\TaskManagement\Support\ContentCalendarPlatformDefaults;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentCalendarController extends Controller
{
    public function __construct(
        protected ContentCalendarItemShareLinkService $itemShareLinks,
        protected ContentCalendarScheduleShareLinkService $scheduleShareLinks,
        protected MediaStorageService $mediaStorage,
        protected ContentCalendarStatusWorkflow $statusWorkflow,
        protected ContentCalendarHolidayService $holidays,
        protected ContentCalendarImporter $importer,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContentCalendarItem::class);

        $clientId = $request->integer('client') ?: Company::query()->orderBy('name')->value('id');
        $period = $this->resolveMonth($request);
        $search = trim((string) $request->string('search'));
        $contentType = $request->string('content_type')->value() ?: null;
        $topic = $request->string('topic')->value() ?: null;
        $platform = $request->string('platform')->value() ?: null;
        $status = $request->string('status')->value() ?: null;
        $holidayFilter = $request->string('holiday')->value() ?: null;
        $sort = $request->string('sort')->value() ?: 'date';
        $direction = strtolower((string) $request->string('direction')->value() ?: 'asc') === 'desc' ? 'desc' : 'asc';

        $company = $clientId ? Company::query()->find($clientId) : null;

        $query = ContentCalendarItem::query()
            ->with(['company:id,name', 'createdBy:id,name', 'updatedBy:id,name', 'media', 'statusHistories.createdBy:id,name', 'platforms'])
            ->when($clientId !== null, fn ($q) => $q->where('tm_company_id', $clientId))
            ->whereDate('scheduled_date', '>=', $period['start'])
            ->whereDate('scheduled_date', '<=', $period['end'])
            ->when($contentType, fn ($q) => $q->where('content_type', $contentType))
            ->when($topic, fn ($q) => $q->where('topic', $topic))
            ->when($platform, fn ($q) => $q->whereHas('platforms', fn ($p) => $p->where('platform', $platform)))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('caption', 'like', "%{$search}%")
                        ->orWhere('hashtags', 'like', "%{$search}%");
                });
            });

        match ($sort) {
            'post_number' => $query->orderBy('post_number', $direction)->orderBy('scheduled_date')->orderBy('id'),
            'status' => $query->orderBy('status', $direction)->orderBy('scheduled_date')->orderBy('id'),
            'topic' => $query->orderBy('topic', $direction)->orderBy('scheduled_date')->orderBy('id'),
            default => $query->orderBy('scheduled_date', $direction)->orderBy('post_number')->orderBy('id'),
        };

        $items = $query->get();

        $monthHolidays = $company
            ? $this->holidays->forCompanyMonth($company, Carbon::parse($period['start']), Carbon::parse($period['end']))
            : [];

        if ($holidayFilter === 'only') {
            $items = $items->filter(fn (ContentCalendarItem $item) => $item->topic === ContentCalendarTopic::FestivalHoliday);
        } elseif ($holidayFilter === 'exclude') {
            $items = $items->reject(fn (ContentCalendarItem $item) => $item->topic === ContentCalendarTopic::FestivalHoliday);
        }

        $plannedCount = $items->count();
        $target = (int) ($company?->monthly_post_target ?? 0);

        $kpis = [
            'monthly_target' => $target,
            'planned' => $plannedCount,
            'remaining' => max(0, $target - $plannedCount),
            'ready' => $items->where('status', ContentCalendarStatus::Ready)->count(),
            'under_review' => $items->where('status', ContentCalendarStatus::UnderReview)->count(),
            'published' => $items->where('status', ContentCalendarStatus::Published)->count(),
            'approved' => $items->where('status', ContentCalendarStatus::Approved)->count(),
            'changes_requested' => $items->where('status', ContentCalendarStatus::ChangesRequested)->count(),
            'not_ready' => $items->whereIn('status', [ContentCalendarStatus::Draft, ContentCalendarStatus::InProgress])->count(),
        ];

        return Inertia::render('TaskManagement/content-calendar/index', [
            'items' => $items->values()->map(fn (ContentCalendarItem $item) => $this->rowPayload($item, $request)),
            'holidays' => $monthHolidays,
            'upcoming_holidays' => $company
                ? $this->holidays->upcoming($company, now()->startOfDay())
                : [],
            'clients' => Company::query()
                ->orderBy('name')
                ->get(['id', 'name', 'monthly_post_target', 'holiday_india_enabled', 'holiday_usa_enabled']),
            'selected_client' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'monthly_post_target' => $company->monthly_post_target,
                'holiday_india_enabled' => $company->holiday_india_enabled,
                'holiday_usa_enabled' => $company->holiday_usa_enabled,
            ] : null,
            'contentTypes' => ContentCalendarType::options(),
            'topics' => ContentCalendarTopic::options(),
            'platforms' => ContentCalendarPlatform::options(),
            'platformDefaults' => ContentCalendarPlatformDefaults::map(),
            'statuses' => ContentCalendarStatus::options(),
            'period' => $period,
            'kpis' => $kpis,
            'filters' => [
                'client' => $clientId,
                'search' => $search !== '' ? $search : null,
                'content_type' => $contentType,
                'topic' => $topic,
                'platform' => $platform,
                'status' => $status,
                'holiday' => $holidayFilter,
                'sort' => $sort,
                'direction' => $direction,
                'view' => $request->string('view')->value() ?: 'table',
            ],
            'import_preview' => $request->session()->get('content_calendar_import_preview'),
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
        $platforms = $validated['platforms'] ?? [];
        unset($validated['files'], $validated['platforms']);

        $item = ContentCalendarItem::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? ContentCalendarStatus::Draft->value,
            'created_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
        ]);

        $item->syncPlatforms($platforms);

        $this->statusWorkflow->recordInitial($item, $request->user());

        $uploaded = false;
        foreach ($files as $file) {
            $item->addMedia($file)
                ->withCustomProperties(['uploaded_by_user_id' => $request->user()->id])
                ->toMediaCollection('attachments');
            $uploaded = true;
        }

        if ($uploaded) {
            $this->statusWorkflow->markReadyAfterUpload($item->fresh(), $request->user());
        }

        return back()->with('success', 'Content scheduled.');
    }

    public function update(ContentCalendarItemRequest $request, ContentCalendarItem $calendarItem): RedirectResponse
    {
        $this->authorize('update', $calendarItem);

        $validated = $request->validated();
        $files = $request->file('files', []) ?? [];
        $platforms = $validated['platforms'] ?? [];
        unset($validated['files'], $validated['platforms']);

        $previousStatus = $calendarItem->status;
        $newStatus = ContentCalendarStatus::from($validated['status']);

        unset($validated['status']);

        $calendarItem->update([
            ...$validated,
            'updated_by_user_id' => $request->user()->id,
        ]);

        $calendarItem->syncPlatforms($platforms);

        if ($previousStatus !== $newStatus) {
            try {
                $this->statusWorkflow->transition($calendarItem->fresh(), $newStatus, $request->user());
            } catch (ProductivityException $exception) {
                return back()->with('error', $exception->getMessage());
            }
        }

        $uploaded = false;
        foreach ($files as $file) {
            $calendarItem->addMedia($file)
                ->withCustomProperties(['uploaded_by_user_id' => $request->user()->id])
                ->toMediaCollection('attachments');
            $uploaded = true;
        }

        if ($uploaded) {
            $this->statusWorkflow->markReadyAfterUpload($calendarItem->fresh(), $request->user());
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

    public function destroyAttachment(ContentCalendarItem $calendarItem, Media $media): RedirectResponse
    {
        $this->authorize('update', $calendarItem);
        $this->assertAttachmentMedia($calendarItem, $media);

        $this->mediaStorage->deleteMedia($media, 'manual_content_calendar_attachment_delete', allowPermanent: true);

        return back()->with('success', 'Attachment removed.');
    }

    public function replaceAttachment(Request $request, ContentCalendarItem $calendarItem, Media $media): RedirectResponse
    {
        $this->authorize('update', $calendarItem);
        $this->assertAttachmentMedia($calendarItem, $media);

        $request->validate([
            'file' => ['required', 'file', 'max:'.UploadLimits::MAX_FILE_KILOBYTES],
        ]);

        $file = $request->file('file');
        $message = UploadLimits::validateContentAttachmentFile($file);
        if ($message !== null) {
            return back()->with('error', $message);
        }

        $this->mediaStorage->deleteMedia($media, 'manual_content_calendar_attachment_replace', allowPermanent: true);

        $calendarItem->addMedia($file)
            ->withCustomProperties(['uploaded_by_user_id' => $request->user()->id])
            ->toMediaCollection('attachments');

        $this->statusWorkflow->markReadyAfterUpload($calendarItem->fresh(), $request->user());

        return back()->with('success', 'Attachment replaced.');
    }

    public function sendForReview(Request $request, ContentCalendarItem $calendarItem): RedirectResponse
    {
        $this->authorize('share', $calendarItem);

        if ($calendarItem->status !== ContentCalendarStatus::Ready
            && $calendarItem->status !== ContentCalendarStatus::ChangesRequested) {
            return back()->with('error', 'Only Post Ready or Changes Requested items can be sent for client review.');
        }

        try {
            if ($calendarItem->status === ContentCalendarStatus::ChangesRequested) {
                $this->statusWorkflow->transition($calendarItem, ContentCalendarStatus::Ready, $request->user(), 'Prepared for re-review');
                $calendarItem = $calendarItem->fresh();
            }

            $this->statusWorkflow->transition(
                $calendarItem,
                ContentCalendarStatus::UnderReview,
                $request->user(),
                'Sent for client review',
            );
        } catch (ProductivityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $link = $this->itemShareLinks->getOrCreate($calendarItem->fresh(), $request->user());

        return back()
            ->with('success', 'Sent for client review.')
            ->with('share_url', $link->publicUrl());
    }

    public function createHolidayPost(Request $request): RedirectResponse
    {
        $this->authorize('create', ContentCalendarItem::class);

        $validated = $request->validate([
            'tm_company_id' => ['required', 'integer', Rule::exists('tm_companies', 'id')],
            'holiday_id' => ['required', 'integer', Rule::exists('tm_holidays', 'id')],
            'content_type' => ['nullable', Rule::enum(ContentCalendarType::class)],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['required', Rule::enum(ContentCalendarPlatform::class)],
        ]);

        $holiday = Holiday::query()->findOrFail($validated['holiday_id']);

        $exists = ContentCalendarItem::query()
            ->where('tm_company_id', $validated['tm_company_id'])
            ->whereDate('scheduled_date', $holiday->date)
            ->where('topic', ContentCalendarTopic::FestivalHoliday->value)
            ->where('description', $holiday->name)
            ->exists();

        if ($exists) {
            return back()->with('error', 'A holiday post for this date already exists.');
        }

        $contentType = ContentCalendarType::tryFrom((string) ($validated['content_type'] ?? ''))
            ?? ContentCalendarType::Poster;

        $platforms = $validated['platforms'] ?? ContentCalendarPlatformDefaults::valuesFor($contentType);
        if ($platforms === []) {
            $platforms = [ContentCalendarPlatform::Instagram->value];
        }

        $item = ContentCalendarItem::query()->create([
            'tm_company_id' => $validated['tm_company_id'],
            'scheduled_date' => $holiday->date->toDateString(),
            'content_type' => $contentType,
            'topic' => ContentCalendarTopic::FestivalHoliday,
            'description' => $holiday->name,
            'status' => ContentCalendarStatus::Draft,
            'created_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
        ]);

        $item->syncPlatforms($platforms);

        $this->statusWorkflow->recordInitial($item, $request->user(), 'Holiday post created');

        return back()->with('success', 'Holiday post created as Post Not Ready.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $this->authorize('create', ContentCalendarItem::class);

        return $this->importer->downloadTemplate();
    }

    public function previewImport(Request $request): RedirectResponse
    {
        $this->authorize('create', ContentCalendarItem::class);

        $validated = $request->validate([
            'client' => ['required', 'integer', Rule::exists('tm_companies', 'id')],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $company = Company::query()->findOrFail($validated['client']);
        $preview = $this->importer->preview($company, $request->file('file'));

        $request->session()->put('content_calendar_import_preview', [
            'client_id' => $company->id,
            'client_name' => $company->name,
            'summary' => $preview['summary'],
            'rows' => $preview['rows'],
            'temp_path' => $request->file('file')->store('content-calendar-imports'),
            'original_name' => $request->file('file')->getClientOriginalName(),
        ]);

        return back()->with('success', 'Import preview ready. Review the rows, then confirm import.');
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        $this->authorize('create', ContentCalendarItem::class);

        $preview = $request->session()->pull('content_calendar_import_preview');

        if (! is_array($preview) || empty($preview['temp_path'])) {
            return back()->with('error', 'Import preview expired. Please upload the file again.');
        }

        $company = Company::query()->findOrFail($preview['client_id']);
        $absolute = Storage::disk('local')->path($preview['temp_path']);

        if (! is_file($absolute)) {
            return back()->with('error', 'Import file missing. Please upload again.');
        }

        $uploaded = new UploadedFile(
            $absolute,
            $preview['original_name'] ?? 'import.xlsx',
            null,
            null,
            true,
        );

        $result = $this->importer->import($company, $uploaded, $request->user(), $this->statusWorkflow);

        @unlink($absolute);

        return back()->with(
            'success',
            sprintf(
                'Import complete: %d created, %d duplicates skipped, %d invalid skipped.',
                $result['imported'],
                $result['skipped_duplicates'],
                $result['skipped_invalid'],
            ),
        );
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
        $period = $this->resolveMonth($request);

        $link = $this->scheduleShareLinks->getOrCreate(
            $client,
            Carbon::parse($period['start']),
            Carbon::parse($period['end']),
            $request->user(),
        );

        return back()->with('success', 'Schedule share link ready.')->with('share_url', $link->publicUrl());
    }

    /**
     * Full calendar month. Accepts `month=YYYY-MM` or legacy `period_start`.
     *
     * @return array{start: string, end: string, label: string, previous_start: string, next_start: string, month: string}
     */
    protected function resolveMonth(Request $request): array
    {
        if ($request->filled('month')) {
            $start = Carbon::createFromFormat('Y-m', (string) $request->string('month'))->startOfMonth();
        } elseif ($request->filled('period_start')) {
            $start = Carbon::parse((string) $request->string('period_start'))->startOfMonth();
        } else {
            $start = now()->startOfMonth();
        }

        $end = $start->copy()->endOfMonth();

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'label' => $start->format('F Y'),
            'month' => $start->format('Y-m'),
            'previous_start' => $start->copy()->subMonth()->startOfMonth()->toDateString(),
            'next_start' => $start->copy()->addMonth()->startOfMonth()->toDateString(),
            'previous_month' => $start->copy()->subMonth()->format('Y-m'),
            'next_month' => $start->copy()->addMonth()->format('Y-m'),
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

        $history = $item->statusHistories->map(fn ($entry) => [
            'from_status' => $entry->from_status?->value,
            'from_status_label' => $entry->from_status?->label(),
            'to_status' => $entry->to_status->value,
            'to_status_label' => $entry->to_status->label(),
            'note' => $entry->note,
            'created_by' => $entry->createdBy?->name,
            'created_at' => $entry->created_at?->toIso8601String(),
        ])->values()->all();

        return [
            'id' => $item->id,
            'scheduled_date' => $item->scheduled_date->toDateString(),
            'scheduled_day' => $item->scheduled_date->format('D'),
            'is_weekend' => $item->scheduled_date->isWeekend(),
            'scheduled_time' => $item->scheduled_time,
            'post_number' => $item->post_number,
            'content_type' => $item->content_type->value,
            'content_type_label' => $item->content_type->label(),
            'topic' => $item->topic->value,
            'topic_label' => $item->topic->label(),
            'platforms' => $item->platforms->map(fn ($row) => [
                'value' => $row->platform->value,
                'label' => $row->platform->label(),
            ])->values()->all(),
            'description' => $item->description,
            'caption' => $item->caption,
            'hashtags' => $item->hashtags,
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'internal_notes' => $item->internal_notes,
            'client_feedback' => $item->client_feedback,
            'published_url' => $item->published_url,
            'published_at' => $item->published_at?->toIso8601String(),
            'reviewed_at' => $item->reviewed_at?->toIso8601String(),
            'uploaded_by' => $item->createdBy->name,
            'updated_by' => $item->updatedBy?->name,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
            'attachments' => $attachments,
            'status_history' => $history,
            'can' => [
                'update' => $request->user()->can('update', $item),
                'delete' => $request->user()->can('delete', $item),
                'share' => $request->user()->can('share', $item),
                'send_for_review' => $request->user()->can('share', $item)
                    && in_array($item->status, [ContentCalendarStatus::Ready, ContentCalendarStatus::ChangesRequested], true),
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
