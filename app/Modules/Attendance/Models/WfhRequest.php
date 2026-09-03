<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Enums\WfhRequestType;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Database\Factories\Attendance\WfhRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property WfhRequestType $type
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $reason
 * @property string|null $notes
 * @property WfhRequestStatus $status
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 * @property int|null $requested_by_user_id
 * @property int|null $assigned_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee $employee
 * @property-read User|null $approver
 * @property-read User|null $requester
 * @property-read User|null $assigner
 */
class WfhRequest extends Model
{
    /** @use HasFactory<WfhRequestFactory> */
    use HasFactory;

    protected $table = 'att_wfh_requests';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'notes',
        'status',
        'approved_by_user_id',
        'approved_at',
        'requested_by_user_id',
        'assigned_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WfhRequestType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => WfhRequestStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeCoveringDate(Builder $query, int $employeeId, Carbon $date): void
    {
        $query->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAuthorizedForDate(Builder $query, int $employeeId, Carbon $date): void
    {
        $query->coveringDate($employeeId, $date)
            ->whereIn('status', [
                WfhRequestStatus::Approved,
                WfhRequestStatus::Assigned,
            ]);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAssignmentForDate(Builder $query, int $employeeId, Carbon $date): void
    {
        $query->coveringDate($employeeId, $date)
            ->where('type', WfhRequestType::Assignment)
            ->where('status', WfhRequestStatus::Assigned);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeApprovedRequestForDate(Builder $query, int $employeeId, Carbon $date): void
    {
        $query->coveringDate($employeeId, $date)
            ->where('type', WfhRequestType::Request)
            ->where('status', WfhRequestStatus::Approved);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOverlappingRange(Builder $query, int $employeeId, Carbon $start, Carbon $end, ?int $excludeId = null): void
    {
        $query->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->whereIn('status', [
                WfhRequestStatus::Pending,
                WfhRequestStatus::Approved,
                WfhRequestStatus::Assigned,
            ])
            ->when($excludeId !== null, fn (Builder $builder) => $builder->where('id', '!=', $excludeId));
    }

    public function coversDate(Carbon $date): bool
    {
        return $date->betweenIncluded($this->start_date->startOfDay(), $this->end_date->startOfDay());
    }

    public function isAuthorized(): bool
    {
        return $this->status->isActiveAuthorization();
    }

    public function isSingleDay(): bool
    {
        return $this->start_date->toDateString() === $this->end_date->toDateString();
    }

    public function dateRangeLabel(): string
    {
        if ($this->isSingleDay()) {
            return $this->start_date->format('M j, Y');
        }

        return $this->start_date->format('M j, Y').' – '.$this->end_date->format('M j, Y');
    }
}
