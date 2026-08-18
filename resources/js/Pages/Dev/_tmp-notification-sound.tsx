import { Button } from '@/components/ui/button';
import { playNotificationSound } from '@/lib/notification-sound';
import { Head } from '@inertiajs/react';

/**
 * TEMPORARY — notification sound QA only.
 * Remove this file and the `/_tmp/notification-sound` route in `routes/web.php` after testing.
 */
export default function TmpNotificationSound() {
    return (
        <>
            <Head title="Test Notification Sound" />
            <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-background p-6 text-foreground">
                <p className="text-sm text-muted-foreground">Temporary page. Safe to delete after testing.</p>
                <Button type="button" onClick={() => playNotificationSound()}>
                    Test Notification Sound
                </Button>
                <p className="max-w-sm text-center text-xs text-muted-foreground">
                    Uses the same Web Audio chime as production. Rapid clicks are ignored for 1.5 seconds.
                </p>
            </div>
        </>
    );
}
