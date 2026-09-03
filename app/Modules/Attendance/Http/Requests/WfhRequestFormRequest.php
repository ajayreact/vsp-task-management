<?php

namespace App\Modules\Attendance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WfhRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\Attendance\Models\WfhRequest::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'date' => ['sometimes', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('date') && ! $this->filled('start_date')) {
            $this->merge([
                'start_date' => $this->input('date'),
            ]);
        }

        if (! $this->filled('end_date')) {
            $this->merge([
                'end_date' => $this->input('start_date'),
            ]);
        }
    }
}
