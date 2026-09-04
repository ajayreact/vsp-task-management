<?php

namespace App\Modules\TaskManagement\Enums;

enum ContractCountry: string
{
    case India = 'india';
    case Usa = 'usa';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::India => 'India',
            self::Usa => 'USA',
            self::Custom => 'Custom',
        };
    }

    public function defaultCurrency(): string
    {
        return match ($this) {
            self::India => 'INR',
            self::Usa => 'USD',
            self::Custom => 'USD',
        };
    }

    public function currencySymbol(): string
    {
        return match ($this) {
            self::India => '₹',
            self::Usa, self::Custom => '$',
        };
    }

    /**
     * @return list<array{value: string, label: string, currency: string, symbol: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $country) => [
                'value' => $country->value,
                'label' => $country->label(),
                'currency' => $country->defaultCurrency(),
                'symbol' => $country->currencySymbol(),
            ],
            self::cases(),
        );
    }
}
