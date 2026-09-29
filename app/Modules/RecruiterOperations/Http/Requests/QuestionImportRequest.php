<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The question import workbook: .xlsx only, up to 5 MB.
 */
class QuestionImportRequest extends FormRequest
{
    public const MAX_KB = 5120;

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
            'file' => ['required', 'file', 'max:'.self::MAX_KB, 'extensions:xlsx', 'mimes:xlsx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.extensions' => 'Upload an .xlsx file made from the template.',
            'file.mimes' => 'Upload an .xlsx file made from the template.',
            'file.max' => 'The file can be at most 5 MB.',
        ];
    }
}
