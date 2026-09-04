<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContractEventType;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Models\ContractEvent;

class ContractEventLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(Contract $contract, ContractEventType $event, ?User $actor = null, ?array $metadata = null): ContractEvent
    {
        return ContractEvent::query()->create([
            'tm_contract_id' => $contract->id,
            'event' => $event,
            'description' => $event->label(),
            'actor_user_id' => $actor?->id,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
