<?php

namespace App\Modules\Core\Http\Requests\Auth;

use App\Modules\Core\Models\User;
use App\Modules\Core\Services\SuperAdminPinAuthenticator;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SuperAdminPinLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxLength = app(SuperAdminPinAuthenticator::class)->inputMaxLength();

        return [
            'pin' => ['required', 'string', 'digits_between:4,'.$maxLength],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'Invalid PIN.',
            'pin.digits_between' => 'Invalid PIN.',
        ];
    }

    /**
     * Authenticate as the Super Admin when the PIN matches.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $authenticator = app(SuperAdminPinAuthenticator::class);

        $this->ensureIsNotRateLimited($authenticator);

        $pin = (string) $this->input('pin');
        $user = $authenticator->resolveSuperAdmin();

        if (! $authenticator->isEnabled()
            || ! $authenticator->pinMatches($pin)
            || $user === null
            || ! $user->isSuperAdmin()
        ) {
            RateLimiter::hit($this->throttleKey(), $authenticator->decaySeconds());
            $authenticator->logAttempt($this, success: false, user: $user);

            throw ValidationException::withMessages([
                'pin' => 'Invalid PIN.',
            ]);
        }

        Auth::guard('web')->login($user, false);
        RateLimiter::clear($this->throttleKey());
        $authenticator->logAttempt($this, success: true, user: $user);

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(SuperAdminPinAuthenticator $authenticator): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $authenticator->maxAttempts())) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'pin' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate('super-admin-pin|'.$this->ip());
    }
}
