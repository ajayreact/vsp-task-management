<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\CompanyDocumentShareLink;
use App\Modules\TaskManagement\Services\CompanyDocumentShareLinkService;
use App\Modules\TaskManagement\Services\DeliverableShareResponder;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class CompanyDocumentShareController extends Controller
{
    public function __construct(
        protected CompanyDocumentShareLinkService $shareLinks,
        protected DeliverableShareResponder $responder,
    ) {}

    public function show(string $token): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderShow($this->shareLinks->resolveByToken($token), preferLegacyUrls: true),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function showShort(string $shortCode): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderShow($this->shareLinks->resolveByShortCode($shortCode)),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    public function file(string $token, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderFile($this->shareLinks->resolveByToken($token), $mediaUuid),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8), 'media_uuid' => $mediaUuid],
        );
    }

    public function fileShort(string $shortCode, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderFile($this->shareLinks->resolveByShortCode($shortCode), $mediaUuid),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode, 'media_uuid' => $mediaUuid],
        );
    }

    public function downloadFile(string $token, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderDownloadFile($this->shareLinks->resolveByToken($token), $mediaUuid),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8), 'media_uuid' => $mediaUuid],
        );
    }

    public function downloadFileShort(string $shortCode, string $mediaUuid): SymfonyResponse|JsonResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderDownloadFile($this->shareLinks->resolveByShortCode($shortCode), $mediaUuid),
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

    protected function renderShow(CompanyDocumentShareLink $link, bool $preferLegacyUrls = false): InertiaResponse
    {
        $document = $link->document;
        $document->loadMissing('company', 'media');
        $media = $document->getFirstMedia('file');

        return Inertia::render('TaskManagement/document-share/show', [
            'brand' => config('app.name'),
            'client_name' => $document->company->name,
            'document' => [
                'title' => $document->title,
                'category' => $document->category->label(),
                'description' => $document->description,
                'file' => $media ? [
                    'name' => $media->file_name,
                    'mime' => $media->mime_type,
                    'size' => $media->size,
                    'preview_url' => $preferLegacyUrls
                        ? route('document-share.file', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                        : $link->publicFileUrl($media->uuid),
                    'download_url' => $preferLegacyUrls
                        ? route('document-share.file.download', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                        : $link->publicFileDownloadUrl($media->uuid),
                    'can_preview' => str_starts_with($media->mime_type, 'image/') || $media->mime_type === 'application/pdf',
                ] : null,
            ],
        ]);
    }

    protected function renderFile(CompanyDocumentShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->documentMediaForLink($link, $mediaUuid);

        if (! is_file($media->getPath())) {
            throw DeliverableShareException::notFound(['missing' => 'file']);
        }

        return $media->toInlineResponse(request());
    }

    protected function renderDownloadFile(CompanyDocumentShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->documentMediaForLink($link, $mediaUuid);

        if (! is_file($media->getPath())) {
            throw DeliverableShareException::notFound(['missing' => 'file']);
        }

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    protected function documentMediaForLink(CompanyDocumentShareLink $link, string $mediaUuid): Media
    {
        $media = Media::query()->where('uuid', $mediaUuid)->first();

        if ($media === null) {
            throw DeliverableShareException::notFound(['missing' => 'media']);
        }

        $documentClass = $link->document->getMorphClass();

        if ($media->collection_name !== 'file'
            || $media->model_type !== $documentClass
            || (int) $media->model_id !== (int) $link->tm_company_document_id) {
            throw DeliverableShareException::unauthorized(['media_uuid' => $mediaUuid]);
        }

        return $media;
    }
}
