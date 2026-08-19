import { subscribeToAttendanceDashboardEvents } from '@/lib/attendance-dashboard-realtime';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export function useAttendanceDashboardRealtime(): void {
    useEffect(() => {
        const unsubscribe = subscribeToAttendanceDashboardEvents(() => {
            router.reload({ only: ['snapshot'] });
        });

        return unsubscribe;
    }, []);
}
