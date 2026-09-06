<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Verifies the Super Admin PIN against SUPER_ADMIN_PIN_HASH and resolves the
 * Spatie `super-admin` account. Never logs or returns the submitted PIN.
 */
class SuperAdminPinAuthenticator
{
    public function isEnabled(): bool
    {
        $hash = (string) config('auth.super_admin_pin.hash', '');

        return $hash !== '';
    }

    public function pinLength(): int
    {
        return max(4, min(12, (int) config('auth.super_admin_pin.pin_length', 6)));
    }

    /**
     * @deprecated Use pinLength(); kept for older call sites.
     */
    public function inputMaxLength(): int
    {
        return $this->pinLength();
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('auth.super_admin_pin.max_attempts', 5));
    }

    public function decaySeconds(): int
    {
        return max(1, (int) config('auth.super_admin_pin.decay_seconds', 60));
    }

    /**
     * Constant-time PIN check against the configured hash.
     */
    public function pinMatches(string $pin): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $hash = (string) config('auth.super_admin_pin.hash');

        return Hash::check($pin, $hash);
    }

    /**
     * Active Super Admin user only. Never returns a non–super-admin account.
     */
    public function resolveSuperAdmin(): ?User
    {
        $user = User::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($query): void {
                $query->where('name', SystemRole::SuperAdmin->value)
                    ->where('guard_name', 'web');
            })
            ->orderBy('id')
            ->first();

        if ($user === null || ! $user->isSuperAdmin()) {
            return null;
        }

        return $user;
    }

    public function logAttempt(Request $request, bool $success, ?User $user = null): void
    {
        $context = [
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'user_id' => $user?->id,
            'success' => $success,
        ];

        if ($success) {
            Log::info('auth.super_admin_pin_succeeded', $context);

            return;
        }

        Log::warning('auth.super_admin_pin_failed', $context);
    }
}
