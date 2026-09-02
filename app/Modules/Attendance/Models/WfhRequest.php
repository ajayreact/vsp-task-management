<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Attendance\Enums\WfhRequestStatus;
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
 * @property Carbon $date
 * @property string $reason
 * @property WfhRequestStatus $status
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee $employee
 * @property-read User|null $approver
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
        'date',
        'reason',
        'status',
        'approved_by_user_id',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
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
     * @param  Builder<$this>  $query
     */
    public function scopeApprovedForDate(Builder $query, int $employeeId, Carbon $date): void
    {
        $query->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->where('status', WfhRequestStatus::Approved);
    }

    public function isApproved(): bool
    {
        return $this->status === WfhRequestStatus::Approved;
    }
}
