<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Services\RecruiterDailyActivityService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * There is deliberately no employee field: an activity always belongs to the
 * person recording it. Authorization runs on the route before this.
 */
class StoreRecruiterDailyActivityRequest extends FormRequest
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
