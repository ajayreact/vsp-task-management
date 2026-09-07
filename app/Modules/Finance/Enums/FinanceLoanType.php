<?php

namespace App\Modules\Finance\Enums;

enum FinanceLoanType: string
{
    case Personal = 'personal';
    case Gold = 'gold';
    case Bank = 'bank';
    case Emi = 'emi';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::Gold => 'Gold',
            self::Bank => 'Bank',
            self::Emi => 'EMI',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
