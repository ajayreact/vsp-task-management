<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceEntry extends Model
{
    protected $table = 'att_attendance_entries';

    protected $fillable = [
        'employee_id',
        'office_location_id',
        'attendance_date',
        'status',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'total_working_seconds',
        'total_break_seconds',
        'net_working_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'status' => AttendanceStatus::class,
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'total_working_seconds' => 'integer',
            'total_break_seconds' => 'integer',
            'net_working_seconds' => 'integer',
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
     * @return BelongsTo<OfficeLocation, $this>
     */
    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class);
    }

    /**
     * @return HasMany<AttendanceBreak, $this>
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class)->orderBy('started_at');
    }

    public function workingStatus(): AttendanceStatus
    {
        if ($this->check_in_at === null) {
            return AttendanceStatus::Present;
        }

        $office = $this->officeLocation;

        if ($office === null) {
            return AttendanceStatus::Present;
        }

        return $office->resolveCheckInStatus($this->check_in_at);
    }
}
