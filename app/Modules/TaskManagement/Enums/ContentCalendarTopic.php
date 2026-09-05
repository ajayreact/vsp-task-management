<?php

namespace App\Modules\TaskManagement\Enums;

enum ContentCalendarTopic: string
{
    case Educational = 'educational';
    case Promotional = 'promotional';
    case Informational = 'informational';
    case Awareness = 'awareness';
    case Engagement = 'engagement';
    case FestivalHoliday = 'festival_holiday';
    case CompanyUpdate = 'company_update';
    case ProductService = 'product_service';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Educational => 'Educational',
            self::Promotional => 'Promotional',
            self::Informational => 'Informational',
            self::Awareness => 'Awareness',
            self::Engagement => 'Engagement',
            self::FestivalHoliday => 'Festival / Holiday',
            self::CompanyUpdate => 'Company Update',
            self::ProductService => 'Product / Service',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $topic) => ['value' => $topic->value, 'label' => $topic->label()],
            self::cases(),
        );
    }
}
