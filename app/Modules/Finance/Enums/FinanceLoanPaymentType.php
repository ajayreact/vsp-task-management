<?php

namespace App\Modules\Finance\Enums;

enum FinanceLoanPaymentType: string
{
    case Emi = 'emi';
    case Partial = 'partial';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Emi => 'EMI',
            self::Partial => 'Partial Repayment',
            self::Full => 'Full Repayment',
        };
    }

    public function expenseDescriptionPrefix(): string
    {
        return match ($this) {
            self::Emi => 'EMI',
            self::Partial => 'Partial repayment',
            self::Full => 'Full repayment',
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
