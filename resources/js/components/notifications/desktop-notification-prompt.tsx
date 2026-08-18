import { Button } from '@/components/ui/button';
import { useBrowserNotificationPermission } from '@/hooks/use-browser-notification-permission';
import { Bell, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const DISMISS_KEY = 'vsp-crm.desktop-notifications.prompt-dismissed';

function wasDismissed(): boolean {
    try {
        return sessionStorage.getItem(DISMISS_KEY) === '1';
    } catch {
        return false;
    }
}

function rememberDismissed(): void {
    try {
        sessionStorage.setItem(DISMISS_KEY, '1');
    } catch {
        // Private mode may block storage.
    }
}

/**
 * Non-intrusive enable banner. Never requests permission on its own.
 */
export function DesktopNotificationPrompt() {
    const { permission, requesting, requestPermission } = useBrowserNotificationPermission();
    const [dismissed, setDismissed] = useState(wasDismissed);

    useEffect(() => {
        if (permission === 'granted' || permission === 'denied') {
            setDismissed(true);
        }
    }, [permission]);

    if (permission !== 'default' || dismissed) {
        return null;
    }

    return (
        <div className="border-border/60 bg-muted/40 mx-4 mt-4 flex items-start gap-3 rounded-xl border px-4 py-3 md:mx-6 md:items-center">
            <Bell className="text-primary mt-0.5 size-4 shrink-0 md:mt-0" strokeWidth={1.75} aria-hidden />
            <div className="min-w-0 flex-1">
                <p className="text-sm font-medium">Enable desktop notifications</p>
                <p className="text-muted-foreground text-xs">Get notified when important work updates happen.</p>
            </div>
            <div className="flex shrink-0 items-center gap-1">
                <Button type="button" size="sm" disabled={requesting} onClick={() => void requestPermission()}>
                    Enable Notifications
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="text-muted-foreground size-8"
                    aria-label="Dismiss desktop notification prompt"
                    onClick={() => {
                        rememberDismissed();
                        setDismissed(true);
                    }}
                >
                    <X className="size-4" strokeWidth={1.75} />
                </Button>
            </div>
        </div>
    );
}
