<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Enums\CompanyLogoVariant;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Models\CompanyShareLink;
use App\Modules\TaskManagement\Services\CompanyShareLinkService;
use App\Modules\TaskManagement\Services\DeliverableShareResponder;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class CompanyShareController extends Controller
{
    public function __construct(
        protected CompanyShareLinkService $shareLinks,
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

    protected function renderShow(CompanyShareLink $link, bool $preferLegacyUrls = false): InertiaResponse
    {
        $company = $link->company;
        $company->loadMissing('media');

        return Inertia::render('TaskManagement/company-share/show', [
            'brand' => config('app.name'),
            'company' => [
                'name' => $company->name,
                'website' => $company->website,
                'email' => $company->primary_contact_email,
                'phone' => $company->primary_contact_phone,
            ],
            'logos' => $this->publicLogos($link, $preferLegacyUrls),
        ]);
    }

    protected function renderFile(CompanyShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->logoMediaForLink($link, $mediaUuid);

        if (! is_file($media->getPath())) {
            throw DeliverableShareException::notFound([
                'share_link_id' => $link->id,
                'company_id' => $link->tm_company_id,
                'missing' => 'file',
            ]);
        }

        return $media->toInlineResponse(request());
    }

    protected function renderDownloadFile(CompanyShareLink $link, string $mediaUuid): SymfonyResponse
    {
        $media = $this->logoMediaForLink($link, $mediaUuid);

        if (! is_file($media->getPath())) {
            throw DeliverableShareException::notFound([
                'share_link_id' => $link->id,
                'company_id' => $link->tm_company_id,
                'missing' => 'file',
            ]);
        }

        return response()->download($media->getPath(), $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function publicLogos(CompanyShareLink $link, bool $preferLegacyUrls = false): array
    {
        return $link->company
            ->getMedia('logos')
            ->sortBy(fn (Media $media) => $media->getCustomProperty('variant'))
            ->map(function (Media $media) use ($link, $preferLegacyUrls): array {
                $variant = CompanyLogoVariant::tryFrom((string) $media->getCustomProperty('variant'));

                return [
                    'name' => $media->file_name,
                    'mime' => $media->mime_type,
                    'size' => $media->size,
                    'variant_label' => $variant?->label() ?? 'Logo',
                    'preview_url' => $preferLegacyUrls
                        ? route('company-share.file', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                        : $link->publicFileUrl($media->uuid),
                    'download_url' => $preferLegacyUrls
                        ? route('company-share.file.download', ['token' => $link->token, 'mediaUuid' => $media->uuid])
                        : $link->publicFileDownloadUrl($media->uuid),
                ];
            })
            ->values()
            ->all();
    }

    protected function logoMediaForLink(CompanyShareLink $link, string $mediaUuid): Media
    {
        $media = Media::query()->where('uuid', $mediaUuid)->first();

        if ($media === null) {
            throw DeliverableShareException::notFound([
                'share_link_id' => $link->id,
                'company_id' => $link->tm_company_id,
                'missing' => 'media',
            ]);
        }

        $companyClass = $link->company->getMorphClass();

        if ($media->collection_name !== 'logos'
            || $media->model_type !== $companyClass
            || (int) $media->model_id !== (int) $link->tm_company_id) {
            throw DeliverableShareException::unauthorized([
                'share_link_id' => $link->id,
                'company_id' => $link->tm_company_id,
                'media_uuid' => $mediaUuid,
            ]);
        }

        return $media;
    }
}
