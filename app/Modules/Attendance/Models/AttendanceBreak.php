<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceBreak extends Model
{
    protected $table = 'att_attendance_breaks';

    protected $fillable = [
        'attendance_entry_id',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AttendanceEntry, $this>
     */
    public function attendanceEntry(): BelongsTo
    {
        return $this->belongsTo(AttendanceEntry::class);
    }
}
