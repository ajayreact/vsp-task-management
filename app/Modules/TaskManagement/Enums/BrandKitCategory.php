<?php

namespace App\Modules\TaskManagement\Enums;

enum BrandKitCategory: string
{
    case Logos = 'logos';
    case Letterheads = 'letterheads';
    case BusinessCards = 'business_cards';
    case EmailSignatures = 'email_signatures';
    case BrandGuidelines = 'brand_guidelines';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Logos => 'Logos',
            self::Letterheads => 'Letterheads',
            self::BusinessCards => 'Business Cards',
            self::EmailSignatures => 'Email Signatures',
            self::BrandGuidelines => 'Brand Guidelines',
            self::Other => 'Other Brand Assets',
        };
    }

    public function tabLabel(): string
    {
        return match ($this) {
            self::Logos => 'Logos',
            self::Letterheads => 'Letterheads',
            self::BusinessCards => 'Business Cards',
            self::EmailSignatures => 'Email Signatures',
            self::BrandGuidelines => 'Brand Guidelines',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string, tab_label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category) => [
                'value' => $category->value,
                'label' => $category->label(),
                'tab_label' => $category->tabLabel(),
            ],
            self::cases(),
        );
    }
}
