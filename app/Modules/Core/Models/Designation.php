<?php

namespace App\Modules\Core\Models;

use Database\Factories\Core\DesignationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Job title catalogue. Separate from Spatie roles: a designation describes
 * what someone does; a role describes what they are allowed to do.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property bool $is_active
 */
class Designation extends Model
{
    /** @use HasFactory<DesignationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
