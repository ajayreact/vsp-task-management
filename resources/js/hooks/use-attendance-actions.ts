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

const INITIAL_STATE: ActionState = {
    phase: 'idle',
    action: null,
    error: null,
};

/**
 * GPS locate → geofence verify (server-side) → persist check-in/out.
 */
export function useAttendanceActions() {
    const [state, setState] = useState<ActionState>(INITIAL_STATE);

    const perform = useCallback(async (action: AttendanceVerificationAction) => {
        setState({
            phase: 'locating',
            action,
            error: null,
        });

        try {
            const coordinates = await getCurrentCoordinates();

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
    }, []);

    const reset = useCallback(() => {
        setState(INITIAL_STATE);
    }, []);

    return {
        ...state,
        isBusy: state.phase !== 'idle',
        perform,
        reset,
    };
}
