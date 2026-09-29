<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Services\RecruiterDailyActivityService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The owner never changes on edit. Authorization runs on the route before this.
 */
class UpdateRecruiterDailyActivityRequest extends FormRequest
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
        return RecruiterDailyActivityService::rules();
    }
}
