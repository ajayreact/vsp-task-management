import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Sign in" />

            <div className="lg:grid lg:min-h-svh lg:grid-cols-[minmax(0,1.05fr)_minmax(24rem,32rem)]">
                <aside className="hidden flex-col justify-between border-r border-[rgba(120,115,110,0.16)] bg-white px-12 py-12 lg:flex xl:px-16">
                    <BrandMark />
                    <div className="max-w-md space-y-3">
                        <p className="text-muted-foreground text-xs font-semibold tracking-[0.16em] uppercase">Workspace</p>
                        <h2 className="text-3xl font-semibold tracking-tight">A quieter place to run the work.</h2>
                        <p className="text-muted-foreground text-sm leading-6">
                            Sign in to manage people, projects, and delivery from one internal workspace.
                        </p>
                    </div>
                    <p className="text-muted-foreground text-xs">VSP CRM</p>
                </aside>

                <main className="flex min-h-svh items-center justify-center px-4 py-10 sm:px-6">
                    <div className="w-full max-w-[26rem]">
                        <div className="mb-8 flex justify-center lg:hidden">
                            <BrandMark />
                        </div>

                        <div className="surface-card border-border/70 rounded-2xl border bg-white p-7 shadow-[0_0.25rem_0.875rem_0_rgba(38,43,67,0.08)] sm:p-8">
                            <div className="mb-7 space-y-1.5">
                                <h1 className="text-2xl font-semibold tracking-tight">Welcome back</h1>
                                <p className="text-muted-foreground text-sm">Sign in to access your workspace.</p>
                            </div>

                            {status && (
                                <div className="bg-accent text-accent-foreground mb-5 rounded-lg px-3 py-2 text-center text-sm font-medium">
                                    {status}
                                </div>
                            )}

                            <form className="space-y-5" onSubmit={submit}>
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="name@company.com"
                                        disabled={processing}
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password">Password</Label>
                                    <div className="relative">
                                        <Input
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="Enter your password"
                                            disabled={processing}
                                            className="pr-11"
                                        />
                                        <button
                                            type="button"
                                            tabIndex={3}
                                            className="text-muted-foreground hover:text-foreground absolute top-1/2 right-3 -translate-y-1/2 rounded-md p-0.5"
                                            onClick={() => setShowPassword((visible) => !visible)}
                                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                                        >
                                            {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                        </button>
                                    </div>
                                    <InputError message={errors.password} />
                                </div>

                                <div className="flex items-center justify-between gap-3">
                                    <label htmlFor="remember" className="flex items-center gap-2.5 text-sm">
                                        <Checkbox
                                            id="remember"
                                            name="remember"
                                            tabIndex={4}
                                            checked={data.remember}
                                            onCheckedChange={(checked) => setData('remember', checked === true)}
                                            disabled={processing}
                                        />
                                        Remember me
                                    </label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={route('password.request')}
                                            className="text-muted-foreground hover:text-foreground text-sm no-underline"
                                            tabIndex={5}
                                        >
                                            Forgot password?
                                        </TextLink>
                                    )}
                                </div>

                                <Button type="submit" className="h-10 w-full" tabIndex={6} disabled={processing}>
                                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                                    {processing ? 'Signing in…' : 'Sign In'}
                                </Button>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}

function BrandMark() {
    return (
        <div className="flex items-center gap-3">
            <div className="bg-primary text-primary-foreground flex size-10 items-center justify-center rounded-xl">
                <AppLogoIcon className="size-5 fill-current" />
            </div>
            <div>
                <p className="text-sm font-semibold tracking-tight">VSP CRM</p>
                <p className="text-muted-foreground text-xs">Internal workspace</p>
            </div>
        </div>
    );
}
