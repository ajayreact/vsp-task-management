<?php

namespace App\Modules\Attendance\Http\Requests;

use App\Modules\Attendance\Models\WfhRequest;
use Illuminate\Foundation\Http\FormRequest;

class WfhSelfRecordFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->isSuperAdmin() || $user->employee === null) {
            return false;
        }

        $record = $this->route('wfhRequest');

        if ($record instanceof WfhRequest) {
            return $record->employee_id === $user->employee->id;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $updating = $this->route('wfhRequest') instanceof WfhRequest;

        return [
            'start_date' => $updating
                ? ['required', 'date']
                : ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('end_date')) {
            $this->merge([
                'end_date' => $this->input('start_date'),
            ]);
        }
    }
}
