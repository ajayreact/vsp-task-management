<?php

namespace App\Modules\TaskManagement\Support;

use App\Modules\TaskManagement\Enums\StorageCategory;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class StorageCategories
{
    /**
     * @return list<StorageCategory>
     */
    public static function temporary(): array
    {
        return array_values(array_filter(
            StorageCategory::cases(),
            fn (StorageCategory $category) => $category->isTemporary(),
        ));
    }

    /**
     * @return list<StorageCategory>
     */
    public static function permanent(): array
    {
        return array_values(array_filter(
            StorageCategory::cases(),
            fn (StorageCategory $category) => ! $category->isTemporary(),
        ));
    }

    public static function forMedia(Media $media): ?StorageCategory
    {
        return match ([$media->model_type, $media->collection_name]) {
            [(new Task)->getMorphClass(), 'attachments'] => StorageCategory::TaskWorkingFiles,
            [(new Deliverable)->getMorphClass(), 'proofs'] => StorageCategory::CreativeReviewFiles,
            [(new Company)->getMorphClass(), 'logos'] => StorageCategory::CompanyLogoLibrary,
            [(new Company)->getMorphClass(), 'brand_assets'] => StorageCategory::CompanyLogoLibrary,
            [(new CompanyDocument)->getMorphClass(), 'file'] => StorageCategory::OperationsDocuments,
            [(new ContentCalendarItem)->getMorphClass(), 'attachments'] => StorageCategory::ContentCalendar,
            default => null,
        };
    }

    public static function isTemporary(Media $media): bool
    {
        $category = self::forMedia($media);

        return $category?->isTemporary() ?? false;
    }

    public static function isPermanent(Media $media): bool
    {
        $category = self::forMedia($media);

        return $category !== null && ! $category->isTemporary();
    }
}
