<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProofRetentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageTaskManagementSettings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'days' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('enabled')) {
                return;
            }

            $days = $this->input('days');

            if ($days === null || $days === '') {
                $validator->errors()->add('days', 'Choose how many days to keep proof files.');

                return;
            }

            if (! is_numeric($days)
                || (int) $days < TaskManagementRetentionService::MIN_DAYS
                || (int) $days > TaskManagementRetentionService::MAX_DAYS) {
                $validator->errors()->add(
                    'days',
                    'Retention days must be between '.TaskManagementRetentionService::MIN_DAYS.' and '.TaskManagementRetentionService::MAX_DAYS.'.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if (! $this->boolean('enabled')) {
            $this->merge(['days' => null]);
        }
    }
}
