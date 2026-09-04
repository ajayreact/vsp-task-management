<?php

namespace App\Modules\TaskManagement\Enums;

enum ContractType: string
{
    case DigitalMarketingLeadGeneration = 'digital_marketing_lead_generation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DigitalMarketingLeadGeneration => 'Digital Marketing & Lead Generation Service Agreement',
            self::Other => 'Other / Custom Contract',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
