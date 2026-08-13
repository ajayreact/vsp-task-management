<?php

namespace App\Modules\Core\Http\Requests\Admin;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleRequest extends FormRequest
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
        $role = $this->route('role');
        $roleId = $role instanceof Role ? $role->id : null;
        $isSystemRole = $role instanceof Role && SystemRole::tryFrom($role->name) !== null;

        return [
            'name' => [
                // Renaming a seeded role would break the code that looks it up
                // by name, so the field is locked once it exists.
                $isSystemRole ? 'prohibited' : 'required',
                'string', 'max:64', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(array_column(Ability::cases(), 'value'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Use lowercase letters, numbers and hyphens only.',
            'name.prohibited' => 'Built-in roles cannot be renamed.',
        ];
    }
}
