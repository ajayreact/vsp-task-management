<?php

namespace App\Modules\Core\Enums;

enum WorkArrangement: string
{
    case Office = 'office';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::Office => 'Office',
            self::Hybrid => 'Hybrid',
            self::Remote => 'Remote',
        };
    }

    public function bypassesOfficeGps(): bool
    {
        return $this === self::Remote;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $arrangement) => ['value' => $arrangement->value, 'label' => $arrangement->label()],
            self::cases(),
        );
    }
}
