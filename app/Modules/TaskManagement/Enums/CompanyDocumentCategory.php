<?php

namespace App\Modules\TaskManagement\Enums;

enum CompanyDocumentCategory: string
{
    case Contract = 'contract';
    case Agreement = 'agreement';
    case Nda = 'nda';
    case Invoice = 'invoice';
    case Legal = 'legal';
    case CompanyDocument = 'company_document';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Contract',
            self::Agreement => 'Agreement',
            self::Nda => 'NDA',
            self::Invoice => 'Invoice',
            self::Legal => 'Legal',
            self::CompanyDocument => 'Company Document',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category) => ['value' => $category->value, 'label' => $category->label()],
            self::cases(),
        );
    }
}
