import {
    getBrowserNotificationPermission,
    requestBrowserNotificationPermission,
    subscribeToBrowserNotificationPermission,
    type BrowserNotificationPermission,
} from '@/lib/browser-notifications';
import { useCallback, useEffect, useState } from 'react';

/**
 * Tracks Notification permission without requesting it on mount.
 */
export function useBrowserNotificationPermission() {
    const [permission, setPermission] = useState<BrowserNotificationPermission>(() => getBrowserNotificationPermission());
    const [requesting, setRequesting] = useState(false);

    useEffect(() => {
        setPermission(getBrowserNotificationPermission());

        return subscribeToBrowserNotificationPermission(setPermission);
    }, []);

    const requestPermission = useCallback(async () => {
        setRequesting(true);

        try {
            const next = await requestBrowserNotificationPermission();
            setPermission(next);

            return next;
        } finally {
            setRequesting(false);
        }
    }, []);

    return {
        permission,
        requesting,
        requestPermission,
        supported: permission !== 'unsupported',
    };
}
