import { subscribeToCommandCenterEvents } from '@/lib/command-center-realtime';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export function useDashboardRealtime(): void {
    useEffect(() => {
        const unsubscribe = subscribeToCommandCenterEvents(() => {
            router.reload({ only: ['snapshot'] });
        });

        return unsubscribe;
    }, []);
}
