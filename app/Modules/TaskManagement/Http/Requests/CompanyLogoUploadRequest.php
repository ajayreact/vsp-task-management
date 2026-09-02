<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\CompanyLogoVariant;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CompanyLogoUploadRequest extends FormRequest
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
            'variant' => ['required', Rule::enum(CompanyLogoVariant::class)],
            'file' => ['required', 'file', 'max:'.UploadLimits::IMAGE_MAX_KILOBYTES],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');

            if ($file === null) {
                return;
            }

            $message = UploadLimits::validateLogoFile($file);

            if ($message !== null) {
                $validator->errors()->add('file', $message);
            }
        });
    }
}
