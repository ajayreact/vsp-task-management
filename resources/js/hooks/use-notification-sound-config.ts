import { configureNotificationSound } from '@/lib/notification-sound';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export function useNotificationSoundConfig(): void {
    const { notificationSound } = usePage<SharedData>().props;

    useEffect(() => {
        configureNotificationSound(notificationSound ?? null);
    }, [notificationSound]);
}
