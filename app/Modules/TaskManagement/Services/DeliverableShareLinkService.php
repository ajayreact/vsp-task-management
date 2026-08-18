<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\DeliverableShareLink;
use Illuminate\Support\Facades\DB;

class DeliverableShareLinkService
{
    /**
     * Return the existing share link for the deliverable, or create one.
     * Repeated calls for the same deliverable reuse the same token and row.
     */
    public function getOrCreate(Deliverable $deliverable, User $user): DeliverableShareLink
    {
        return DB::transaction(function () use ($deliverable, $user): DeliverableShareLink {
            Deliverable::query()->whereKey($deliverable->id)->lockForUpdate()->firstOrFail();

            $existing = DeliverableShareLink::query()
                ->where('tm_deliverable_id', $deliverable->id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return DeliverableShareLink::query()->create([
                'tm_deliverable_id' => $deliverable->id,
                'token' => bin2hex(random_bytes(32)),
                'created_by_user_id' => $user->id,
            ]);
        });
    }
}
