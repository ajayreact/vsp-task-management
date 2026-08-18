<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\AppSetting;
use App\Modules\TaskManagement\Models\Deliverable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskManagementRetentionService
{
    public const SETTINGS_GROUP = 'task_management';

    public const SETTINGS_KEY = 'proof_retention';

    public const MIN_DAYS = 1;

    public const MAX_DAYS = 3650;

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

    public function isEligible(Deliverable $deliverable): bool
    {
        $policy = $this->policy();

        if (! $policy['enabled'] || $policy['days'] === null) {
            return false;
        }

        return $deliverable->submitted_at->lte(now()->subDays($policy['days']));
    }

    /**
     * @return Collection<int, Deliverable>
     */
    public function eligibleDeliverables(): Collection
    {
        $policy = $this->policy();

        if (! $policy['enabled'] || $policy['days'] === null) {
            return collect();
        }

        return Deliverable::query()
            ->where('submitted_at', '<=', now()->subDays($policy['days']))
            ->get();
    }

    /**
     * Retention cleanup: drop every proofs file on this deliverable, then
     * drop its share link if none remain. Leaves the deliverable row, reviews,
     * and task history in place. No-ops when the policy does not apply.
     */
    public function cleanup(Deliverable $deliverable): void
    {
        if (! $this->isEligible($deliverable)) {
            return;
        }

        $deliverable->clearMediaCollection('proofs');
        $this->forgetShareLinkIfEmpty($deliverable);
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

        $deliverable = Deliverable::query()->findOrFail($media->model_id);
        $media->delete();
        $this->forgetShareLinkIfEmpty($deliverable);
    }

    protected function daysAreValid(?int $days): bool
    {
        return $days !== null
            && $days >= self::MIN_DAYS
            && $days <= self::MAX_DAYS;
    }

    protected function forgetShareLinkIfEmpty(Deliverable $deliverable): void
    {
        $deliverable->unsetRelation('media');
        $deliverable->unsetRelation('shareLink');

        if ($deliverable->getMedia('proofs')->isNotEmpty()) {
            return;
        }

        $deliverable->shareLink?->delete();
    }
}
