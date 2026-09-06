<?php

namespace App\Modules\Finance\Enums;

enum FinanceExpenseCategory: string
{
    case Personal = 'personal';
    case Travel = 'travel';
    case Food = 'food';
    case Shopping = 'shopping';
    case Medical = 'medical';
    case Family = 'family';
    case Business = 'business';
    case LoanPayment = 'loan_payment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::Travel => 'Travel',
            self::Food => 'Food',
            self::Shopping => 'Shopping',
            self::Medical => 'Medical',
            self::Family => 'Family',
            self::Business => 'Business',
            self::LoanPayment => 'Loan Payment',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category) => ['value' => $category->value, 'label' => $category->label()],
            self::cases(),
        );
    }
}
