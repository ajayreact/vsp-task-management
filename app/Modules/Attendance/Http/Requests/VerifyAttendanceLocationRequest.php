<?php

namespace App\Modules\Attendance\Http\Requests;

use App\Modules\Attendance\Enums\AttendanceAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyAttendanceLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('markOwnAttendance') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(AttendanceAction::class)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
