export class GeolocationError extends Error {
    constructor(message: string) {
        super(message);
        this.name = 'GeolocationError';
    }
}

export interface CurrentCoordinates {
    latitude: number;
    longitude: number;
    accuracyMeters: number | null;
}

/**
 * Reads the browser/device GPS fix. Requires a secure context (HTTPS or localhost).
 */
export function getCurrentCoordinates(options?: { timeoutMs?: number }): Promise<CurrentCoordinates> {
    const timeoutMs = options?.timeoutMs ?? 15_000;

    return new Promise((resolve, reject) => {
        if (!('geolocation' in navigator)) {
            reject(new GeolocationError('This browser does not support location access.'));

            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracyMeters: Number.isFinite(position.coords.accuracy) ? position.coords.accuracy : null,
                });
            },
            (error) => {
                reject(new GeolocationError(mapGeolocationError(error)));
            },
            {
                enableHighAccuracy: true,
                timeout: timeoutMs,
                maximumAge: 0,
            },
        );
    });
}

function mapGeolocationError(error: GeolocationPositionError): string {
    switch (error.code) {
        case error.PERMISSION_DENIED:
            return 'Location permission was denied. Allow location access to mark attendance.';
        case error.POSITION_UNAVAILABLE:
            return 'Unable to read your current location.';
        case error.TIMEOUT:
            return 'Location request timed out. Try again.';
        default:
            return 'Unable to read your current location.';
    }
}

function readXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export type AttendanceVerificationAction = 'check_in' | 'check_out';

export interface LocationVerificationPayload {
    passed: boolean;
    message: string;
    action: AttendanceVerificationAction;
    distance_meters: number;
    allowed_radius_meters: number;
    office: { id: number; name: string } | null;
}

export async function verifyAttendanceLocation(
    action: AttendanceVerificationAction,
    coordinates: Pick<CurrentCoordinates, 'latitude' | 'longitude'>,
): Promise<LocationVerificationPayload> {
    const response = await fetch('/attendance/verify-location', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': readXsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            action,
            latitude: coordinates.latitude,
            longitude: coordinates.longitude,
        }),
    });

    const payload = (await response.json()) as LocationVerificationPayload;

    if (!response.ok && response.status !== 422) {
        throw new Error(payload.message || 'Unable to verify your location.');
    }

    return payload;
}
