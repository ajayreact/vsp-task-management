import {
    GeolocationError,
    getCurrentCoordinates,
    verifyAttendanceLocation,
    type AttendanceVerificationAction,
    type LocationVerificationPayload,
} from '@/lib/attendance/geolocation';
import { useCallback, useState } from 'react';

type VerificationPhase = 'idle' | 'locating' | 'verifying';

interface VerifyState {
    phase: VerificationPhase;
    action: AttendanceVerificationAction | null;
    result: LocationVerificationPayload | null;
    error: string | null;
}

const INITIAL_STATE: VerifyState = {
    phase: 'idle',
    action: null,
    result: null,
    error: null,
};

/**
 * Shared check-in/check-out geofence flow for the next attendance steps.
 */
export function useAttendanceGeofence() {
    const [state, setState] = useState<VerifyState>(INITIAL_STATE);

    const verify = useCallback(async (action: AttendanceVerificationAction) => {
        setState({
            phase: 'locating',
            action,
            result: null,
            error: null,
        });

        try {
            const coordinates = await getCurrentCoordinates();

            setState((current) => ({
                ...current,
                phase: 'verifying',
            }));

            const result = await verifyAttendanceLocation(action, coordinates);

            setState({
                phase: 'idle',
                action,
                result,
                error: result.passed ? null : result.message,
            });

            return result;
        } catch (error) {
            const message = error instanceof GeolocationError ? error.message : 'Unable to verify your location.';

            setState({
                phase: 'idle',
                action,
                result: null,
                error: message,
            });

            throw error;
        }
    }, []);

    const reset = useCallback(() => {
        setState(INITIAL_STATE);
    }, []);

    const isBusy = state.phase !== 'idle';

    return {
        ...state,
        isBusy,
        verify,
        reset,
    };
}
