<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\AppSetting;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Support\StorageCategories;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskManagementRetentionService
{
    public const SETTINGS_GROUP = 'task_management';

    public const SETTINGS_KEY = 'proof_retention';

    public const MIN_DAYS = 1;

    public const MAX_DAYS = 3650;

    public function __construct(protected MediaStorageService $mediaStorage) {}

    /**
     * @return array{enabled: bool, days: int|null}
     */
    public function policy(): array
    {
        $payload = AppSetting::payload(self::SETTINGS_GROUP, self::SETTINGS_KEY);

        $days = $payload['days'] ?? null;

        return [
            'enabled' => (bool) ($payload['enabled'] ?? false),
            'days' => is_numeric($days) ? (int) $days : null,
        ];
    }

    public function writePolicy(bool $enabled, ?int $days): void
    {
        if ($enabled && ! $this->daysAreValid($days)) {
            throw new InvalidArgumentException(
                'Retention days must be an integer between '.self::MIN_DAYS.' and '.self::MAX_DAYS.'.'
            );
        }

        if ($days !== null && ! $this->daysAreValid($days)) {
            throw new InvalidArgumentException(
                'Retention days must be an integer between '.self::MIN_DAYS.' and '.self::MAX_DAYS.'.'
            );
        }

        AppSetting::put(self::SETTINGS_GROUP, self::SETTINGS_KEY, [
            'enabled' => $enabled,
            'days' => $days,
        ]);
    }

    public function cutoff(): ?Carbon
    {
        $policy = $this->policy();

        if (! $policy['enabled'] || $policy['days'] === null) {
            return null;
        }

        return now()->subDays($policy['days']);
    }

    public function isEligible(Deliverable $deliverable): bool
    {
        return $this->eligibleProofMediaForDeliverable($deliverable)->isNotEmpty();
    }

    /**
     * @return Collection<int, Deliverable>
     */
    public function eligibleDeliverables(): Collection
    {
        $cutoff = $this->cutoff();

        if ($cutoff === null) {
            return collect();
        }

        return Deliverable::query()
            ->whereHas('media', function ($query) use ($cutoff): void {
                $query->where('collection_name', 'proofs')
                    ->where('created_at', '<=', $cutoff);
            })
            ->get();
    }

    public function isMediaEligible(Media $media): bool
    {
        $cutoff = $this->cutoff();

        if ($cutoff === null || ! StorageCategories::isTemporary($media)) {
            return false;
        }

        return $media->created_at->lte($cutoff);
    }

    /**
     * @return Collection<int, Media>
     */
    public function eligibleTemporaryMedia(): Collection
    {
        $cutoff = $this->cutoff();

        if ($cutoff === null) {
            return collect();
        }

        $taskMorph = (new Task)->getMorphClass();
        $deliverableMorph = (new Deliverable)->getMorphClass();

        return Media::query()
            ->where('created_at', '<=', $cutoff)
            ->where(function ($query) use ($taskMorph, $deliverableMorph): void {
                $query->where(function ($query) use ($taskMorph): void {
                    $query->where('model_type', $taskMorph)
                        ->where('collection_name', 'attachments');
                })->orWhere(function ($query) use ($deliverableMorph): void {
                    $query->where('model_type', $deliverableMorph)
                        ->where('collection_name', 'proofs');
                });
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{deleted: int, missing_files: int, skipped: int}
     */
    public function runCleanup(): array
    {
        $deleted = 0;
        $missingFiles = 0;
        $skipped = 0;

        foreach ($this->eligibleTemporaryMedia() as $media) {
            if (! $this->isMediaEligible($media)) {
                $skipped++;

                continue;
            }

            $result = $this->mediaStorage->deleteMedia($media, 'automatic_retention');
            $deleted++;

            if ($result['missing_file']) {
                $missingFiles++;
            }
        }

        return [
            'deleted' => $deleted,
            'missing_files' => $missingFiles,
            'skipped' => $skipped,
        ];
    }

    /**
     * Retention cleanup for a single deliverable's eligible proof files.
     */
    public function cleanup(Deliverable $deliverable): void
    {
        foreach ($this->eligibleProofMediaForDeliverable($deliverable) as $media) {
            $this->mediaStorage->deleteMedia($media, 'automatic_retention');
        }
    }

    /**
     * Manual single-file removal. Only Task Management deliverable "proofs"
     * media is accepted. Share links stay while any proof remains.
     */
    public function deleteProof(Media $media): void
    {
        $deliverableClass = (new Deliverable)->getMorphClass();

        if ($media->collection_name !== 'proofs' || $media->model_type !== $deliverableClass) {
            throw new InvalidArgumentException('Only Task Management deliverable proofs can be deleted.');
        }

        $this->mediaStorage->deleteMedia($media, 'manual_proof_delete', allowPermanent: false);
    }

    /**
     * @return Collection<int, Media>
     */
    protected function eligibleProofMediaForDeliverable(Deliverable $deliverable): Collection
    {
        $cutoff = $this->cutoff();

        if ($cutoff === null) {
            return collect();
        }

        return $deliverable->getMedia('proofs')
            ->filter(fn (Media $media) => $media->created_at->lte($cutoff))
            ->values();
    }

    protected function daysAreValid(?int $days): bool
    {
        return $days !== null
            && $days >= self::MIN_DAYS
            && $days <= self::MAX_DAYS;
    }
}
