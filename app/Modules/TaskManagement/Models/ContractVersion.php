<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContractVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $tm_contract_id
 * @property int $version_number
 * @property ContractVersionStatus $status
 * @property array<string, mixed> $snapshot
 * @property string|null $change_summary
 */
class ContractVersion extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'tm_contract_versions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_contract_id',
        'version_number',
        'status',
        'snapshot',
        'change_summary',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContractVersionStatus::class,
            'snapshot' => 'array',
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pdf');
        $this->addMediaCollection('signed_pdf');
    }
}
