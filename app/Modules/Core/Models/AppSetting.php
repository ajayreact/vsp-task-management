<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * JSON key/value store. `value` is an array payload for a unique (group, key).
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property array<string, mixed>|null $value
 */
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

    /**
     * @return array<string, mixed>
     */
    public static function payload(string $group, string $key): array
    {
        return static::query()
            ->where('group', $group)
            ->where('key', $key)
            ->firstOrNew()
            ->value ?? [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function put(string $group, string $key, array $value): self
    {
        return static::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value],
        );
    }
}
