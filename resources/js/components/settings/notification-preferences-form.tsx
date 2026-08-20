import InputError from '@/components/input-error';
import { SettingsCard, SettingsFormFooter } from '@/components/settings/settings-card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { type NotificationPreferences } from '@/types';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { BellRing } from 'lucide-react';
import { FormEventHandler } from 'react';

export function NotificationPreferencesForm({ preferences }: { preferences: NotificationPreferences }) {
    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        browser_notifications: preferences.browser_notifications,
        notification_sound: preferences.notification_sound,
        in_app_notifications: preferences.in_app_notifications,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('notification-preferences.update'));
    };

    return (
        <SettingsCard
            title="Notification preferences"
            description="Control how new task alerts appear on this account. Browser permission is still managed by your browser."
            icon={BellRing}
            tone="sky"
            footer={
                <SettingsFormFooter>
                    <Button type="submit" form="notification-preferences-form" disabled={processing}>
                        Save preferences
                    </Button>
                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-muted-foreground text-sm font-medium">Saved</p>
                    </Transition>
                </SettingsFormFooter>
            }
        >
            <form id="notification-preferences-form" onSubmit={submit} className="space-y-5">
                <div className="flex items-center justify-between gap-4 rounded-xl border px-4 py-3">
                    <div>
                        <Label htmlFor="in_app_notifications">In-app notifications</Label>
                        <p className="text-muted-foreground text-sm">Show toast alerts when a new notification arrives.</p>
                    </div>
                    <Switch
                        id="in_app_notifications"
                        checked={data.in_app_notifications}
                        onCheckedChange={(checked) => setData('in_app_notifications', checked)}
                    />
                </div>

                <div className="flex items-center justify-between gap-4 rounded-xl border px-4 py-3">
                    <div>
                        <Label htmlFor="notification_sound">Notification sound</Label>
                        <p className="text-muted-foreground text-sm">Play the configured alert sound for new notifications.</p>
                    </div>
                    <Switch
                        id="notification_sound"
                        checked={data.notification_sound}
                        onCheckedChange={(checked) => setData('notification_sound', checked)}
                    />
                </div>

                <div className="flex items-center justify-between gap-4 rounded-xl border px-4 py-3">
                    <div>
                        <Label htmlFor="browser_notifications">Browser notifications</Label>
                        <p className="text-muted-foreground text-sm">Allow desktop alerts when your browser permission is granted.</p>
                    </div>
                    <Switch
                        id="browser_notifications"
                        checked={data.browser_notifications}
                        onCheckedChange={(checked) => setData('browser_notifications', checked)}
                    />
                </div>

                <InputError message={errors.browser_notifications || errors.notification_sound || errors.in_app_notifications} />
            </form>
        </SettingsCard>
    );
}
