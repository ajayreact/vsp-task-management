<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\CompanyDocumentCategory;
use App\Modules\TaskManagement\Http\Requests\CompanyDocumentRequest;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Services\CompanyDocumentShareLinkService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CompanyDocumentController extends Controller
{
    public function __construct(protected CompanyDocumentShareLinkService $shareLinks) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CompanyDocument::class);

        $search = trim((string) $request->string('search'));
        $clientId = $request->integer('client') ?: null;
        $category = $request->string('category')->value() ?: null;

        $documents = CompanyDocument::query()
            ->with(['company:id,name', 'createdBy:id,name', 'media'])
            ->when($clientId !== null, fn ($query) => $query->where('tm_company_id', $clientId))
            ->when($category !== null && $category !== '', fn ($query) => $query->where('category', $category))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(Pagination::perPage($request))
            ->withQueryString()
            ->through(fn (CompanyDocument $document) => $this->rowPayload($document, $request));

        return Inertia::render('TaskManagement/documents/index', [
            'documents' => $documents,
            'clients' => Company::query()->orderBy('name')->get(['id', 'name']),
            'categories' => CompanyDocumentCategory::options(),
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'client' => $clientId,
                'category' => $category,
            ],
            'can' => [
                'manage' => $request->user()->can('create', CompanyDocument::class),
                'share' => $request->user()->can(Ability::ShareCompanyDocuments->value),
            ],
        ]);
    }

    public function store(CompanyDocumentRequest $request): RedirectResponse
    {
        $this->authorize('create', CompanyDocument::class);

        $validated = $request->validated();
        $file = $request->file('file');

        unset($validated['file']);

        $document = CompanyDocument::query()->create([
            ...$validated,
            'created_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
        ]);

        $document->addMedia($file)
            ->withCustomProperties(['uploaded_by_user_id' => $request->user()->id])
            ->toMediaCollection('file');

        return back()->with('success', 'Document uploaded.');
    }

    public function update(CompanyDocumentRequest $request, CompanyDocument $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validated();
        $file = $request->file('file');

        unset($validated['file']);

        $document->update([
            ...$validated,
            'updated_by_user_id' => $request->user()->id,
        ]);

        if ($file !== null) {
            $document->clearMediaCollection('file');
            $document->addMedia($file)
                ->withCustomProperties(['uploaded_by_user_id' => $request->user()->id])
                ->toMediaCollection('file');
        }

        return back()->with('success', 'Document updated.');
    }

    public function destroy(CompanyDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    public function preview(Request $request, CompanyDocument $document): SymfonyResponse
    {
        $this->authorize('view', $document);

        $media = $this->fileMedia($document);

        if (! is_file($media->getPath())) {
            abort(404);
        }

        return $media->toInlineResponse($request);
    }

    public function download(Request $request, CompanyDocument $document): SymfonyResponse
    {
        $this->authorize('view', $document);

        $media = $this->fileMedia($document);

        if (! is_file($media->getPath())) {
            abort(404);
        }

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    public function shareLink(Request $request, CompanyDocument $document): RedirectResponse
    {
        $this->authorize('share', $document);

        $link = $this->shareLinks->getOrCreate($document, $request->user());

        return back()->with('success', 'Share link ready.')->with('share_url', $link->publicUrl());
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowPayload(CompanyDocument $document, Request $request): array
    {
        $media = $document->getFirstMedia('file');

        return [
            'id' => $document->id,
            'title' => $document->title,
            'category' => $document->category->value,
            'category_label' => $document->category->label(),
            'description' => $document->description,
            'client' => [
                'id' => $document->company->id,
                'name' => $document->company->name,
            ],
            'uploaded_by' => $document->createdBy->name,
            'uploaded_at' => $document->created_at->toIso8601String(),
            'updated_at' => $document->updated_at->toIso8601String(),
            'file' => $media ? [
                'uuid' => $media->uuid,
                'name' => $media->file_name,
                'mime' => $media->mime_type,
                'size' => $media->size,
                'preview_url' => route('tasks.documents.preview', $document),
                'download_url' => route('tasks.documents.download', $document),
                'can_preview' => $this->canPreview($media),
            ] : null,
            'can' => [
                'update' => $request->user()->can('update', $document),
                'delete' => $request->user()->can('delete', $document),
                'share' => $request->user()->can('share', $document),
            ],
        ];
    }

    protected function fileMedia(CompanyDocument $document): Media
    {
        $media = $document->getFirstMedia('file');

        abort_if($media === null, 404);

        return $media;
    }

    protected function canPreview(Media $media): bool
    {
        return str_starts_with($media->mime_type, 'image/')
            || $media->mime_type === 'application/pdf';
    }
}
