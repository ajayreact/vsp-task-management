import { subscribeToAttendanceDashboardEvents } from '@/lib/attendance-dashboard-realtime';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export function useAttendanceDashboardRealtime(enabled = true): void {
    useEffect(() => {
        if (! enabled) {
            return;
        }

        const unsubscribe = subscribeToAttendanceDashboardEvents(() => {
            router.reload({ only: ['snapshot', 'dailyTable'] });
        });

        return unsubscribe;
    }, [enabled]);
}
