import InputError from '@/components/input-error';
import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { previewNotificationSound } from '@/lib/notification-sound';
import { NOTIFICATION_SOUND_ACCEPT, validateNotificationSound } from '@/lib/upload-limits';
import { router, useForm } from '@inertiajs/react';
import { Play, Upload, Volume2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';

export interface NotificationSoundSettingsPayload {
    enabled: boolean;
    source: 'system' | 'custom';
    system_sound: string;
    custom: {
        media_id: number | null;
        file_name: string | null;
        has_file: boolean;
    };
    system_sounds: { value: string; label: string; url: string }[];
}

export function NotificationSoundSettings({ settings }: { settings: NotificationSoundSettingsPayload }) {
    const uploadInputRef = useRef<HTMLInputElement>(null);
    const [uploadError, setUploadError] = useState<string | null>(null);

    const form = useForm({
        enabled: settings.enabled,
        source: settings.source,
        system_sound: settings.system_sound,
    });

    const customPreviewUrl = '/tasks/notification-sound/custom';

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Volume2 className="size-4" /> Notification Sound
                </CardTitle>
                <CardDescription>
                    Choose the company-wide sound that plays when staff receive a new realtime notification.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="flex items-center gap-3">
                    <Switch
                        id="notification_sound_enabled"
                        checked={form.data.enabled}
                        onCheckedChange={(checked) => form.setData('enabled', checked)}
                    />
                    <Label htmlFor="notification_sound_enabled" className="font-normal">
                        Enable notification sound
                    </Label>
                </div>

                <div className="space-y-3">
                    <p className="text-sm font-medium">Sound source</p>
                    <div className="flex flex-wrap gap-4 text-sm">
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                name="notification_sound_source"
                                checked={form.data.source === 'system'}
                                onChange={() => form.setData('source', 'system')}
                            />
                            System sounds
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                name="notification_sound_source"
                                checked={form.data.source === 'custom'}
                                onChange={() => form.setData('source', 'custom')}
                                disabled={!settings.custom.has_file}
                            />
                            Custom upload
                        </label>
                    </div>
                    {!settings.custom.has_file && (
                        <p className="text-muted-foreground text-xs">Upload a custom sound below before selecting custom upload.</p>
                    )}
                </div>

                {form.data.source === 'system' && (
                    <div className="space-y-3">
                        <p className="text-sm font-medium">Available sounds</p>
                        <div className="space-y-2">
                            {settings.system_sounds.map((sound) => {
                                const selected = form.data.system_sound === sound.value;

                                return (
                                    <div
                                        key={sound.value}
                                        className="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3 text-sm"
                                    >
                                        <div className="font-medium">{sound.label}</div>
                                        <div className="flex items-center gap-2">
                                            <Button type="button" variant="outline" size="sm" onClick={() => previewNotificationSound(sound.url)}>
                                                <Play className="size-3.5" /> Preview
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant={selected ? 'default' : 'outline'}
                                                onClick={() => form.setData('system_sound', sound.value)}
                                            >
                                                {selected ? 'Selected' : 'Select'}
                                            </Button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                <div className="space-y-4 rounded-lg border p-4">
                    <div>
                        <p className="text-sm font-medium">Custom notification sound</p>
                        <p className="text-muted-foreground text-xs">MP3, WAV, or OGG up to 5 MB. Only one custom sound is kept.</p>
                    </div>

                    {settings.custom.has_file && (
                        <div className="flex flex-wrap items-center justify-between gap-3 rounded-md bg-muted/40 p-3 text-sm">
                            <div>
                                <p className="font-medium">Current</p>
                                <p className="text-muted-foreground">{settings.custom.file_name}</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => previewNotificationSound(customPreviewUrl)}>
                                    <Play className="size-3.5" /> Preview
                                </Button>
                                <Button type="button" variant="outline" size="sm" onClick={() => uploadInputRef.current?.click()}>
                                    Replace
                                </Button>
                                <ConfirmDelete
                                    url="/tasks/settings/notification-sound/custom"
                                    title="Delete custom notification sound?"
                                    description="Staff will fall back to the selected system sound."
                                    trigger={
                                        <Button type="button" variant="outline" size="sm" className="text-destructive hover:text-destructive">
                                            Delete
                                        </Button>
                                    }
                                />
                            </div>
                        </div>
                    )}

                    <div className="flex flex-wrap items-center gap-3">
                        <input
                            ref={uploadInputRef}
                            type="file"
                            accept={NOTIFICATION_SOUND_ACCEPT}
                            className="hidden"
                            onChange={(event) => {
                                const file = event.target.files?.[0];

                                if (!file) {
                                    return;
                                }

                                const validationError = validateNotificationSound(file);

                                if (validationError) {
                                    setUploadError(validationError);
                                    toast.error(validationError);

                                    if (uploadInputRef.current) {
                                        uploadInputRef.current.value = '';
                                    }

                                    return;
                                }

                                setUploadError(null);
                                router.post(
                                    '/tasks/settings/notification-sound/custom',
                                    { sound: file },
                                    {
                                        forceFormData: true,
                                        preserveScroll: true,
                                        onFinish: () => {
                                            if (uploadInputRef.current) {
                                                uploadInputRef.current.value = '';
                                            }
                                        },
                                    },
                                );
                            }}
                        />
                        <Button type="button" variant="outline" onClick={() => uploadInputRef.current?.click()}>
                            <Upload className="size-4" /> {settings.custom.has_file ? 'Upload replacement' : 'Upload sound'}
                        </Button>
                        <InputError message={uploadError ?? undefined} />
                    </div>
                </div>

                <InputError message={form.errors.source || form.errors.system_sound || form.errors.enabled} />

                <Button
                    type="button"
                    disabled={form.processing}
                    onClick={() => {
                        form.put('/tasks/settings/notification-sound', { preserveScroll: true });
                    }}
                >
                    Save notification sound
                </Button>
            </CardContent>
        </Card>
    );
}
