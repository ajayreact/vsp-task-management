<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Support\StorageCategories;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class MediaStorageService
{
    /**
     * @return array{deleted: bool, missing_file: bool, media_id: int, uuid: string, category: string|null}
     */
    public function deleteMedia(Media $media, string $reason, bool $allowPermanent = false): array
    {
        $category = StorageCategories::forMedia($media);

        if ($category === null) {
            throw new InvalidArgumentException('Unsupported media storage category.');
        }

        if ($category->isTemporary() === false && ! $allowPermanent) {
            throw new InvalidArgumentException('Automatic cleanup cannot delete permanent storage files.');
        }

        $this->assertPathIsWithinConfiguredDisk($media);

        $missingFile = ! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot());
        $mediaId = (int) $media->id;
        $uuid = $media->uuid;

        try {
            $media->delete();
        } catch (Throwable $exception) {
            Log::error('media.delete_failed', [
                'reason' => $reason,
                'media_id' => $mediaId,
                'uuid' => $uuid,
                'category' => $category->value,
                'missing_file' => $missingFile,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::info('media.deleted', [
            'reason' => $reason,
            'media_id' => $mediaId,
            'uuid' => $uuid,
            'category' => $category->value,
            'missing_file' => $missingFile,
        ]);

        if ($category->value === 'creative_review_files') {
            $this->forgetDeliverableShareLinkIfEmpty((int) $media->model_id);
        }

        return [
            'deleted' => true,
            'missing_file' => $missingFile,
            'media_id' => $mediaId,
            'uuid' => $uuid,
            'category' => $category->value,
        ];
    }

    protected function assertPathIsWithinConfiguredDisk(Media $media): void
    {
        $diskRoot = realpath(Storage::disk($media->disk)->path(''));

        if ($diskRoot === false) {
            throw new RuntimeException("Storage disk root is unavailable for [{$media->disk}].");
        }

        $absolutePath = $media->getPath();

        if (! is_file($absolutePath)) {
            return;
        }

        $resolvedPath = realpath($absolutePath);

        if ($resolvedPath === false || ! str_starts_with($resolvedPath, $diskRoot)) {
            throw new RuntimeException('Refusing to delete media outside the configured storage disk.');
        }
    }

    protected function forgetDeliverableShareLinkIfEmpty(int $deliverableId): void
    {
        $deliverable = Deliverable::query()->find($deliverableId);

        if ($deliverable === null) {
            return;
        }

        $deliverable->unsetRelation('media');
        $deliverable->unsetRelation('shareLink');

        if ($deliverable->getMedia('proofs')->isNotEmpty()) {
            return;
        }

        $deliverable->shareLink?->delete();
    }
}
