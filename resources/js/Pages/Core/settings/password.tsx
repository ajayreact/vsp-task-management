import InputError from '@/components/input-error';
import { SettingsCard, SettingsFormFooter } from '@/components/settings/settings-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: '/settings/password',
    },
];

export default function Password() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Password settings" />

            <SettingsLayout>
                <SettingsCard
                    title="Update password"
                    description="Choose a strong password you do not use anywhere else."
                    icon={Lock}
                    tone="sky"
                    footer={
                        <SettingsFormFooter>
                            <Button type="submit" form="password-form" disabled={processing}>
                                Save password
                            </Button>
                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-muted-foreground text-sm font-medium">Password updated</p>
                            </Transition>
                        </SettingsFormFooter>
                    }
                >
                    <form id="password-form" onSubmit={updatePassword} className="space-y-5">
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="current_password">Current password</Label>
                                <Input
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    value={data.current_password}
                                    onChange={(e) => setData('current_password', e.target.value)}
                                    type="password"
                                    autoComplete="current-password"
                                    placeholder="Enter current password"
                                />
                                <InputError message={errors.current_password} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="password">New password</Label>
                                    <Input
                                        id="password"
                                        ref={passwordInput}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        type="password"
                                        autoComplete="new-password"
                                        placeholder="Enter new password"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">Confirm password</Label>
                                    <Input
                                        id="password_confirmation"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        type="password"
                                        autoComplete="new-password"
                                        placeholder="Confirm new password"
                                    />
                                    <InputError message={errors.password_confirmation} />
                                </div>
                            </div>
                        </div>

                        <p className="text-muted-foreground text-xs leading-relaxed">
                            Use at least 12 characters with a mix of letters, numbers, and symbols.
                        </p>
                    </form>
                </SettingsCard>
            </SettingsLayout>
        </AppLayout>
    );
}
