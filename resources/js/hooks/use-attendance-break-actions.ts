import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

type BreakAction = 'start' | 'resume';

interface BreakActionState {
    action: BreakAction | null;
    isSubmitting: boolean;
    error: string | null;
}

const INITIAL_STATE: BreakActionState = {
    action: null,
    isSubmitting: false,
    error: null,
};

export function useAttendanceBreakActions() {
    const [state, setState] = useState<BreakActionState>(INITIAL_STATE);

    const perform = useCallback(async (action: BreakAction) => {
        setState({
            action,
            isSubmitting: true,
            error: null,
        });

        const url = action === 'start' ? '/attendance/break/start' : '/attendance/break/resume';

        await new Promise<void>((resolve) => {
            router.post(
                url,
                {},
                {
                    preserveScroll: true,
                    onFinish: () => {
                        setState({
                            action: null,
                            isSubmitting: false,
                            error: null,
                        });
                        resolve();
                    },
                },
            );
        });
    }, []);

    const reset = useCallback(() => {
        setState(INITIAL_STATE);
    }, []);

    return {
        ...state,
        isBusy: state.isSubmitting,
        perform,
        reset,
    };
}
