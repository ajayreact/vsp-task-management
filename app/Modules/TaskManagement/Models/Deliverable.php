<?php

namespace App\Modules\TaskManagement\Models;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use Database\Factories\TaskManagement\DeliverableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $tm_task_id
 * @property int $version
 * @property int $submitted_by_employee_id
 * @property DeliverableStatus $status
 * @property string|null $notes
 * @property string|null $client_feedback
 * @property Carbon $submitted_at
 * @property-read Task $task
 * @property-read Employee $submitter
 * @property-read DeliverableShareLink|null $shareLink
 */
class Deliverable extends Model implements HasMedia
{
    /** @use HasFactory<DeliverableFactory> */
    use HasFactory, InteractsWithMedia;

    protected $table = 'tm_deliverables';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tm_task_id',
        'version',
        'submitted_by_employee_id',
        'status',
        'notes',
        'client_feedback',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DeliverableStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tm_task_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'submitted_by_employee_id');
    }

    /**
     * @return HasMany<DeliverableReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(DeliverableReview::class, 'tm_deliverable_id');
    }

    /**
     * @return HasOne<DeliverableShareLink, $this>
     */
    public function shareLink(): HasOne
    {
        return $this->hasOne(DeliverableShareLink::class, 'tm_deliverable_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('proofs');
    }
}
