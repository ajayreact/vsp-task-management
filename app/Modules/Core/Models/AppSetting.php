<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
