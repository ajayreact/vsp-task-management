<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContractEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractEvent extends Model
{
    protected $table = 'tm_contract_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_contract_id',
        'event',
        'description',
        'actor_user_id',
        'metadata',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => ContractEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'tm_contract_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
