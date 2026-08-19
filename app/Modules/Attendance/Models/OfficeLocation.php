<?php

namespace App\Modules\Attendance\Models;

use Database\Factories\Attendance\OfficeLocationFactory;
use App\Modules\Attendance\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class OfficeLocation extends Model
{
    /** @use HasFactory<OfficeLocationFactory> */
    use HasFactory;

    protected $table = 'att_office_locations';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'allowed_gps_radius_meters',
        'late_check_in_time',
        'network_verification_enabled',
        'authorized_public_ips',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'allowed_gps_radius_meters' => 'integer',
            'network_verification_enabled' => 'boolean',
            'authorized_public_ips' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<EmployeeOfficeAssignment, $this>
     */
    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeOfficeAssignment::class);
    }

    public function resolveCheckInStatus(Carbon $checkInAt): AttendanceStatus
    {
        if ($this->late_check_in_time === null) {
            return AttendanceStatus::Present;
        }

        $deadline = Carbon::parse(today()->toDateString().' '.$this->late_check_in_time);

        return $checkInAt->greaterThan($deadline)
            ? AttendanceStatus::Late
            : AttendanceStatus::Present;
    }

    protected static function newFactory(): OfficeLocationFactory
    {
        return OfficeLocationFactory::new();
    }
}
