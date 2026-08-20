import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { NotificationPreferencesForm } from '@/components/settings/notification-preferences-form';
import { SettingsCard, SettingsFormFooter } from '@/components/settings/settings-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem, type NotificationPreferences, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { User } from 'lucide-react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

export default function Profile({
    mustVerifyEmail,
    status,
    notificationPreferences,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    notificationPreferences: NotificationPreferences;
}) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user.name,
        email: auth.user.email,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <SettingsCard
                    title="Profile information"
                    description="Update your name and the email address you use to sign in."
                    icon={User}
                    tone="indigo"
                    footer={
                        <SettingsFormFooter>
                            <Button type="submit" form="profile-form" disabled={processing}>
                                Save changes
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
                    <form id="profile-form" onSubmit={submit} className="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Full name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    autoComplete="name"
                                    placeholder="Your name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoComplete="username"
                                    placeholder="you@studio.com"
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        {mustVerifyEmail && auth.user.email_verified_at === null && (
                            <div className="rounded-xl border border-amber-200/80 bg-amber-50/80 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-100">
                                <p>
                                    Your email address is unverified.{' '}
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="text-primary font-medium underline underline-offset-2"
                                    >
                                        Resend verification email
                                    </Link>
                                </p>
                                {status === 'verification-link-sent' && (
                                    <p className="mt-2 font-medium text-emerald-700 dark:text-emerald-400">
                                        A new verification link has been sent.
                                    </p>
                                )}
                            </div>
                        )}
                    </form>
                </SettingsCard>

                <NotificationPreferencesForm preferences={notificationPreferences} />

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
