<?php

namespace App\Modules\TaskManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tm_company_id
 * @property string|null $label
 * @property string $phone
 * @property bool $is_primary
 * @property int $sort_order
 */
class CompanyPhoneNumber extends Model
{
    protected $table = 'tm_company_phone_numbers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_company_id',
        'label',
        'phone',
        'is_primary',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tm_company_id');
    }
}
