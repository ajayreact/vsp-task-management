<?php

namespace App\Modules\TaskManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'signer_name' => ['required', 'string', 'max:255'],
            'authorized_person' => ['nullable', 'string', 'max:255'],
            'signature_type' => ['required', Rule::in(['drawn', 'uploaded', 'typed'])],
            'signature_data' => ['required', 'string', 'max:500000'],
            'agreed' => ['accepted'],
        ];
    }
}
