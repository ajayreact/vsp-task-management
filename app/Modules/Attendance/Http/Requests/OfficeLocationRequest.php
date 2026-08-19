<?php

namespace App\Modules\Attendance\Http\Requests;

use App\Modules\Attendance\Models\OfficeLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OfficeLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAttendance') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $raw = (string) $this->input('authorized_public_ips_text', '');
        $parsed = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $raw) ?: [])));

        $this->merge([
            'authorized_public_ips' => $parsed,
            'late_check_in_time' => $this->normalizeLateCheckInTime(
                (string) $this->input('late_check_in_time', ''),
            ),
        ]);
    }

    protected function normalizeLateCheckInTime(string $value): string
    {
        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value.':00';
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $office = $this->route('office_location');
        $officeId = $office instanceof OfficeLocation ? $office->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('att_office_locations', 'name')->ignore($officeId),
            ],
            'address' => ['required', 'string', 'max:2000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'allowed_gps_radius_meters' => ['required', 'integer', 'min:1', 'max:10000'],
            'late_check_in_time' => ['required', 'date_format:H:i:s'],
            'network_verification_enabled' => ['required', 'boolean'],
            'authorized_public_ips_text' => ['nullable', 'string', 'max:5000'],
            'authorized_public_ips' => [
                Rule::excludeIf(fn () => ! $this->boolean('network_verification_enabled')),
                Rule::requiredIf(fn () => $this->boolean('network_verification_enabled')),
                'array',
                'min:1',
            ],
            'authorized_public_ips.*' => [
                Rule::excludeIf(fn () => ! $this->boolean('network_verification_enabled')),
                'required',
                'string',
                'max:45',
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('authorized_public_ips', []) as $index => $address) {
                if (! $this->isValidNetworkAddress((string) $address)) {
                    $validator->errors()->add(
                        'authorized_public_ips_text',
                        'Each authorized public IP must be a valid IPv4/IPv6 address or CIDR range.',
                    );

                    break;
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'allowed_gps_radius_meters.min' => 'The allowed GPS radius must be at least 1 meter.',
            'allowed_gps_radius_meters.max' => 'The allowed GPS radius may not exceed 10,000 meters.',
            'authorized_public_ips.required' => 'Add at least one authorized public IP when network verification is enabled.',
            'authorized_public_ips.min' => 'Add at least one authorized public IP when network verification is enabled.',
        ];
    }

    /**
     * @return array<string, mixed>|mixed
     */
    public function validated($key = null, $default = null): mixed
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated();

        unset($validated['authorized_public_ips_text']);

        if (! ($validated['network_verification_enabled'] ?? false)) {
            $validated['authorized_public_ips'] = null;
        }

        if ($key !== null) {
            return data_get($validated, $key, $default);
        }

        return $validated;
    }

    protected function isValidNetworkAddress(string $value): bool
    {
        if (str_contains($value, '/')) {
            [$subnet, $mask] = explode('/', $value, 2);

            if (filter_var($subnet, FILTER_VALIDATE_IP) === false) {
                return false;
            }

            if (! is_numeric($mask)) {
                return false;
            }

            $mask = (int) $mask;

            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $mask >= 0 && $mask <= 32;
            }

            return $mask >= 0 && $mask <= 128;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
