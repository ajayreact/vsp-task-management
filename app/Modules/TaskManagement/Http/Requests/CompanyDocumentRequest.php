<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\CompanyDocumentCategory;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CompanyDocumentRequest extends FormRequest
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
        $rules = [
            'tm_company_id' => ['required', 'integer', Rule::exists('tm_companies', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(CompanyDocumentCategory::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->isMethod('post')) {
            $rules['file'] = ['required', 'file', 'max:'.UploadLimits::DOCUMENT_MAX_KILOBYTES];
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['file'] = ['nullable', 'file', 'max:'.UploadLimits::DOCUMENT_MAX_KILOBYTES];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');

            if ($file === null) {
                return;
            }

            $message = UploadLimits::validateDocumentFile($file);

            if ($message !== null) {
                $validator->errors()->add('file', $message);
            }
        });
    }
}
