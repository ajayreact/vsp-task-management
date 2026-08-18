import { NotificationSoundSettings, type NotificationSoundSettingsPayload } from '@/components/admin/notification-sound-settings';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/admin/page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import TaskLayout from '@/layouts/task-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    retention: {
        enabled: boolean;
        days: number | null;
    };
    notificationSound: NotificationSoundSettingsPayload;
}

const PRESETS = [7, 15, 30] as const;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: '/tasks' },
    { title: 'Task Management Settings', href: '/tasks/settings' },
];

function isPreset(days: number | null): days is (typeof PRESETS)[number] {
    return days !== null && PRESETS.includes(days as (typeof PRESETS)[number]);
}

export default function TaskManagementSettings({ retention, notificationSound }: Props) {
    const { data, setData, put, processing, errors } = useForm<{ enabled: boolean; days: number | null }>({
        enabled: retention.enabled,
        days: retention.days,
    });
    const [usingCustom, setUsingCustom] = useState(!isPreset(retention.days) && retention.days !== null);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put('/tasks/settings', { preserveScroll: true });
    };

    const choosePreset = (days: (typeof PRESETS)[number]) => {
        setUsingCustom(false);
        setData('days', days);
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="Task Management Settings" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Task Management Settings"
                    description="Organisation-wide options for Task Management. Only Operations Head can change them."
                />

                <form onSubmit={submit} className="max-w-2xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Proof File Retention</CardTitle>
                            <CardDescription>
                                Configure how long completed creative proof files are kept before automatic cleanup.
                                Automatic cleanup deletes only creative proof files on Task Management deliverables.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="flex items-center gap-3">
                                <Switch
                                    id="retention_enabled"
                                    checked={data.enabled}
                                    onCheckedChange={(checked) => {
                                        setData({
                                            enabled: checked,
                                            days: checked ? (data.days ?? 7) : data.days,
                                        });
                                        if (checked && data.days === null) {
                                            setUsingCustom(false);
                                        }
                                    }}
                                />
                                <Label htmlFor="retention_enabled" className="font-normal">
                                    Enable automatic proof cleanup
                                </Label>
                            </div>

                            {!data.enabled && <p className="text-sm font-medium">Keep proof files forever</p>}

                            {data.enabled && (
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <p className="text-sm font-medium">Retention period</p>
                                        <div className="flex flex-wrap gap-2">
                                            {PRESETS.map((days) => (
                                                <Button
                                                    key={days}
                                                    type="button"
                                                    size="sm"
                                                    variant={!usingCustom && data.days === days ? 'default' : 'outline'}
                                                    onClick={() => choosePreset(days)}
                                                >
                                                    {days} Days
                                                </Button>
                                            ))}
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant={usingCustom ? 'default' : 'outline'}
                                                onClick={() => setUsingCustom(true)}
                                            >
                                                Custom
                                            </Button>
                                        </div>
                                    </div>

                                    {usingCustom && (
                                        <div className="grid max-w-xs gap-2">
                                            <Label htmlFor="retention_days">Retention days</Label>
                                            <Input
                                                id="retention_days"
                                                type="number"
                                                min={1}
                                                max={3650}
                                                value={data.days ?? ''}
                                                onChange={(event) =>
                                                    setData('days', event.target.value === '' ? null : Number(event.target.value))
                                                }
                                            />
                                            <InputError message={errors.days} />
                                        </div>
                                    )}

                                    {!usingCustom && <InputError message={errors.days} />}

                                    <p className="text-muted-foreground text-sm">
                                        Proof files will be automatically deleted after the selected retention period.
                                    </p>
                                </div>
                            )}

                            <Alert>
                                <AlertTitle>Cleanup scope</AlertTitle>
                                <AlertDescription>
                                    Automatic cleanup deletes only creative proof files. Tasks, projects, clients, review
                                    history, working files, and CRM files will not be deleted.
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>

                    <NotificationSoundSettings settings={notificationSound} />

                    <Button type="submit" disabled={processing}>
                        Save settings
                    </Button>
                </form>
            </div>
        </TaskLayout>
    );
}
