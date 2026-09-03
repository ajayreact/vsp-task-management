<?php

namespace App\Modules\TaskManagement\Enums;

enum StorageCategory: string
{
    case TaskWorkingFiles = 'task_working_files';
    case CreativeReviewFiles = 'creative_review_files';
    case CompanyLogoLibrary = 'company_logo_library';
    case OperationsDocuments = 'operations_documents';
    case ContentCalendar = 'content_calendar';

    public function isTemporary(): bool
    {
        return match ($this) {
            self::TaskWorkingFiles, self::CreativeReviewFiles => true,
            self::CompanyLogoLibrary, self::OperationsDocuments, self::ContentCalendar => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TaskWorkingFiles => 'Working Files',
            self::CreativeReviewFiles => 'Creative Review / Proof Files',
            self::CompanyLogoLibrary => 'Company Logo Library',
            self::OperationsDocuments => 'Operations Documents',
            self::ContentCalendar => 'Content Calendar',
        };
    }
}
