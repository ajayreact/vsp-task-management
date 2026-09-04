<?php

namespace App\Modules\TaskManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSignature extends Model
{
    protected $table = 'tm_contract_signatures';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_contract_id',
        'tm_contract_version_id',
        'party',
        'signer_name',
        'authorized_person',
        'signature_type',
        'signature_data',
        'ip_address',
        'user_agent',
        'signed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
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
     * @return BelongsTo<ContractVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'tm_contract_version_id');
    }
}
