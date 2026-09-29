<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Decline, hold, reopen and cancel all need a written reason for the timeline.
 */
class RecruiterTaskReasonRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
