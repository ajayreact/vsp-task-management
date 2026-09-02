import {
    GeolocationError,
    getCurrentCoordinates,
    type AttendanceVerificationAction,
} from '@/lib/attendance/geolocation';
import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

type ActionPhase = 'idle' | 'locating' | 'submitting';

interface ActionState {
    phase: ActionPhase;
    action: AttendanceVerificationAction | null;
    error: string | null;
}

interface AttendanceCoordinates {
    latitude: number;
    longitude: number;
}

interface UseAttendanceActionsOptions {
    locationBypassEnabled?: boolean;
    fallbackCoordinates?: AttendanceCoordinates | null;
}

const INITIAL_STATE: ActionState = {
    phase: 'idle',
    action: null,
    error: null,
};

/**
 * GPS locate → geofence verify (server-side) → persist check-in/out.
 * Super Admin bypass skips browser geolocation when fallback coordinates are available.
 */
export function useAttendanceActions(options: UseAttendanceActionsOptions = {}) {
    const { locationBypassEnabled = false, fallbackCoordinates = null } = options;
    const [state, setState] = useState<ActionState>(INITIAL_STATE);

    const resolveCoordinates = useCallback(async (): Promise<AttendanceCoordinates> => {
        if (locationBypassEnabled && fallbackCoordinates !== null) {
            return fallbackCoordinates;
        }

        if (locationBypassEnabled) {
            try {
                return await getCurrentCoordinates({ timeoutMs: 5_000 });
            } catch {
                throw new GeolocationError(
                    'Unable to determine a location reference. Add an office location or allow GPS access.',
                );
            }
        }

        return getCurrentCoordinates();
    }, [fallbackCoordinates, locationBypassEnabled]);

    const perform = useCallback(
        async (action: AttendanceVerificationAction) => {
            setState({
                phase: 'locating',
                action,
                error: null,
            });

            try {
                const coordinates = await resolveCoordinates();

                setState((current) => ({
                    ...current,
                    phase: 'submitting',
                }));

                const url = action === 'check_in' ? '/attendance/check-in' : '/attendance/check-out';

                await new Promise<void>((resolve, reject) => {
                    router.post(
                        url,
                        {
                            latitude: coordinates.latitude,
                            longitude: coordinates.longitude,
                        },
                        {
                            preserveScroll: true,
                            onSuccess: () => resolve(),
                            onError: (errors) => {
                                const message =
                                    typeof errors.latitude === 'string'
                                        ? errors.latitude
                                        : 'Unable to mark attendance.';

                                reject(new Error(message));
                            },
                            onFinish: () => {
                                setState({
                                    phase: 'idle',
                                    action: null,
                                    error: null,
                                });
                            },
                        },
                    );
                });
            } catch (error) {
                const message = error instanceof GeolocationError ? error.message : 'Unable to mark attendance.';

                setState({
                    phase: 'idle',
                    action,
                    error: message,
                });

                throw error;
            }
        },
        [resolveCoordinates],
    );

    const performWfh = useCallback(async (action: AttendanceVerificationAction) => {
        setState({
            phase: 'submitting',
            action,
            error: null,
        });

        const url = action === 'check_in' ? '/attendance/check-in/wfh' : '/attendance/check-out/wfh';

        try {
            await new Promise<void>((resolve, reject) => {
                router.post(
                    url,
                    {},
                    {
                        preserveScroll: true,
                        onSuccess: () => resolve(),
                        onError: () => reject(new Error('Unable to mark WFH attendance.')),
                        onFinish: () => {
                            setState({
                                phase: 'idle',
                                action: null,
                                error: null,
                            });
                        },
                    },
                );
            });
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Unable to mark WFH attendance.';
            setState({
                phase: 'idle',
                action,
                error: message,
            });
            throw error;
        }
    }, []);

    const reset = useCallback(() => {
        setState(INITIAL_STATE);
    }, []);

    return {
        ...state,
        isBusy: state.phase !== 'idle',
        perform,
        performWfh,
        reset,
    };
}
