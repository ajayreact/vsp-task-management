<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\BrandKitCategory;
use App\Modules\TaskManagement\Enums\CompanyLogoVariant;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BrandKitAssetUploadRequest extends FormRequest
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
        $category = BrandKitCategory::tryFrom((string) $this->input('category'));

        $rules = [
            'category' => ['required', Rule::enum(BrandKitCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file'],
        ];

        if ($category === BrandKitCategory::Logos) {
            $rules['variant'] = ['required', Rule::enum(CompanyLogoVariant::class)];
            $rules['files'] = ['required', 'array', 'size:1'];
            $rules['files.*'] = ['required', 'file'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var list<UploadedFile>|null $files */
            $files = $this->file('files');

            if (! is_array($files) || $files === []) {
                return;
            }

            if (UploadLimits::combinedUploadBytes($files) > UploadLimits::DOCUMENTED_POST_MAX_BYTES) {
                $validator->errors()->add('files', UploadLimits::combinedRequestExceededMessage());
            }

            $category = BrandKitCategory::tryFrom((string) $this->input('category'));

            foreach ($files as $index => $file) {
                $message = $category === BrandKitCategory::Logos
                    ? UploadLimits::validateLogoFile($file)
                    : UploadLimits::validateProofFile($file);

                if ($message !== null) {
                    $validator->errors()->add("files.{$index}", $message);
                }
            }
        });
    }
}
