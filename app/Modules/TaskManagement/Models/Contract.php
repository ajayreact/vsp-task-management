<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContractCountry;
use App\Modules\TaskManagement\Enums\ContractStatus;
use App\Modules\TaskManagement\Enums\ContractType;
use Database\Factories\TaskManagement\ContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $contract_number
 * @property string $title
 * @property ContractType $contract_type
 * @property ContractCountry $country
 * @property string $currency
 * @property ContractStatus $status
 * @property int $tm_company_id
 * @property \Illuminate\Support\Carbon $effective_date
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $current_version_id
 * @property int|null $original_document_id
 * @property int|null $signed_document_id
 * @property \Illuminate\Support\Carbon|null $signed_at
 */
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'tm_contracts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contract_number',
        'title',
        'contract_type',
        'country',
        'currency',
        'status',
        'tm_company_id',
        'effective_date',
        'start_date',
        'end_date',
        'current_version_id',
        'original_document_id',
        'signed_document_id',
        'signed_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contract_type' => ContractType::class,
            'country' => ContractCountry::class,
            'status' => ContractStatus::class,
            'effective_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tm_company_id');
    }

    /**
     * @return BelongsTo<ContractVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<ContractVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class, 'tm_contract_id')->orderByDesc('version_number');
    }

    /**
     * @return HasOne<ContractShareLink, $this>
     */
    public function shareLink(): HasOne
    {
        return $this->hasOne(ContractShareLink::class, 'tm_contract_id');
    }

    /**
     * @return HasMany<ContractSignature, $this>
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class, 'tm_contract_id');
    }

    /**
     * @return HasMany<ContractEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ContractEvent::class, 'tm_contract_id')->orderByDesc('occurred_at');
    }

    /**
     * @return BelongsTo<CompanyDocument, $this>
     */
    public function originalDocument(): BelongsTo
    {
        return $this->belongsTo(CompanyDocument::class, 'original_document_id');
    }

    /**
     * @return BelongsTo<CompanyDocument, $this>
     */
    public function signedDocument(): BelongsTo
    {
        return $this->belongsTo(CompanyDocument::class, 'signed_document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task-management')
            ->logOnly(['title', 'status', 'contract_number', 'currency'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
