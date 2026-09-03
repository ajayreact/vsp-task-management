<?php

namespace App\Modules\TaskManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandKitCompanyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'phones' => ['nullable', 'array'],
            'phones.*.id' => [
                'nullable',
                'integer',
                Rule::exists('tm_company_phone_numbers', 'id')->where('tm_company_id', $this->route('company')?->id),
            ],
            'phones.*.label' => ['nullable', 'string', 'max:50'],
            'phones.*.phone' => ['required', 'string', 'max:32'],
            'phones.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
