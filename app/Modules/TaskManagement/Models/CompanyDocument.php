<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\CompanyDocumentCategory;
use Database\Factories\TaskManagement\CompanyDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $tm_company_id
 * @property string $title
 * @property CompanyDocumentCategory $category
 * @property string|null $description
 * @property int $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property-read Company $company
 * @property-read User $createdBy
 * @property-read User|null $updatedBy
 */
class CompanyDocument extends Model implements HasMedia
{
    /** @use HasFactory<CompanyDocumentFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $table = 'tm_company_documents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_company_id',
        'title',
        'category',
        'description',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => CompanyDocumentCategory::class,
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tm_company_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * @return HasOne<CompanyDocumentShareLink, $this>
     */
    public function shareLink(): HasOne
    {
        return $this->hasOne(CompanyDocumentShareLink::class, 'tm_company_document_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task-management')
            ->logOnly(['title', 'category', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
