<?php

namespace App\Modules\Attendance\Support;

use App\Modules\Attendance\Models\OfficeLocation;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Web browsers cannot read Wi-Fi SSID/BSSID. When employees use office Wi-Fi,
 * their requests exit through the office public IP — that is what we verify.
 */
class OfficeNetworkVerifier
{
    public const UNAUTHORIZED_NETWORK_MESSAGE = 'You must be connected to the authorized office network to mark attendance.';

    public function isAuthorized(?string $clientIp, OfficeLocation $office): bool
    {
        if (! $office->network_verification_enabled) {
            return true;
        }

        if ($clientIp === null || $clientIp === '') {
            return false;
        }

        $authorizedIps = $office->authorized_public_ips ?? [];

        if ($authorizedIps === []) {
            return false;
        }

        foreach ($authorizedIps as $allowed) {
            if (IpUtils::checkIp($clientIp, (string) $allowed)) {
                return true;
            }
        }

        return false;
    }
}
