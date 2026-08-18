<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class DeliverableProofRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:1000'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
        ];
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

                return;
            }

            foreach ($files as $index => $file) {
                $message = UploadLimits::validateProofFile($file);

                if ($message !== null) {
                    $validator->errors()->add('files.'.$index, $message);
                }
            }
        });
    }
}
