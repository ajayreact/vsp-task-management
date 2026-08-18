import BrandLogo from '@/components/brand-logo';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Head, useForm } from '@inertiajs/react';
import {
    Eye,
    EyeOff,
    FolderKanban,
    LoaderCircle,
    Lock,
    Mail,
    Radio,
    ShieldCheck,
    Users,
} from 'lucide-react';
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

const featureHighlights = [
    { icon: Users, label: 'Team & Task Management' },
    { icon: FolderKanban, label: 'Projects & Creative Delivery' },
    { icon: Radio, label: 'Real-Time Collaboration' },
] as const;

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
        <div className="bg-background text-foreground min-h-svh overflow-x-hidden">
            <Head title="Sign in" />

            <div className="lg:grid lg:min-h-svh lg:grid-cols-[minmax(0,1.08fr)_minmax(26rem,34rem)] xl:grid-cols-[minmax(0,1.15fr)_minmax(28rem,36rem)]">
                <aside className="relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between">
                    <div
                        aria-hidden="true"
                        className="from-primary/[0.07] via-background to-primary/[0.12] absolute inset-0 bg-gradient-to-br"
                    />
                    <div
                        aria-hidden="true"
                        className="absolute inset-0 opacity-[0.35] dark:opacity-[0.2]"
                        style={{
                            backgroundImage:
                                'radial-gradient(circle at 1px 1px, color-mix(in srgb, var(--primary) 14%, transparent) 1px, transparent 0)',
                            backgroundSize: '28px 28px',
                        }}
                    />

                    <div
                        aria-hidden="true"
                        className="login-float-slow border-primary/10 bg-primary/[0.06] absolute -top-16 -right-10 size-72 rounded-full border"
                    />
                    <div
                        aria-hidden="true"
                        className="login-float-slower border-primary/10 bg-primary/[0.04] absolute top-1/3 -left-20 size-56 rounded-3xl border rotate-12"
                    />
                    <div
                        aria-hidden="true"
                        className="login-float-slow border-primary/10 absolute right-16 bottom-32 size-40 rounded-full border bg-white/40 dark:bg-white/5"
                    />

                    <div className="relative z-10 px-12 pt-12 xl:px-16 xl:pt-14">
                        <BrandMark />
                    </div>

                    <div className="relative z-10 flex flex-1 flex-col justify-center px-12 py-10 xl:px-16">
                        <div className="max-w-lg space-y-8">
                            <div className="space-y-4">
                                <h2 className="text-4xl leading-[1.08] font-semibold tracking-tight xl:text-[2.65rem]">
                                    Manage work.
                                    <br />
                                    Move faster.
                                    <br />
                                    <span className="text-primary">Stay aligned.</span>
                                </h2>
                                <p className="text-muted-foreground max-w-md text-base leading-7">
                                    One secure workspace for your team, projects, tasks and creative delivery.
                                </p>
                            </div>

                            <ul className="space-y-3.5">
                                {featureHighlights.map(({ icon: Icon, label }) => (
                                    <li key={label} className="flex items-center gap-3 text-sm">
                                        <span className="bg-primary/10 text-primary flex size-8 shrink-0 items-center justify-center rounded-lg">
                                            <Icon className="size-4" aria-hidden="true" />
                                        </span>
                                        <span className="text-foreground/90 font-medium">{label}</span>
                                    </li>
                                ))}
                            </ul>

                            <div className="border-primary/15 bg-primary/[0.04] text-muted-foreground inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-medium">
                                <ShieldCheck className="text-primary size-3.5 shrink-0" aria-hidden="true" />
                                Secure workspace
                            </div>
                        </div>
                    </div>

                    <div className="text-muted-foreground relative z-10 px-12 pb-12 xl:px-16 xl:pb-14">
                        <p className="text-xs leading-5">© 2026 VSP CRM</p>
                    </div>
                </aside>

                <main className="bg-background flex min-h-svh flex-col items-center justify-center px-4 py-10 sm:px-6 lg:px-10 xl:px-14">
                    <div className="w-full max-w-[27rem]">
                        <div className="mb-8 flex justify-center lg:hidden">
                            <BrandLogo variant="loginMobile" />
                        </div>

                        <div
                            className={cn(
                                'border-border/60 bg-card rounded-[1.35rem] border p-7 shadow-[0_1rem_2.5rem_-0.75rem_rgba(38,43,67,0.14)] sm:p-9',
                                'motion-safe:animate-in motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-3 motion-safe:duration-700',
                            )}
                        >
                            <div className="mb-8 space-y-4">
                                <BrandLogo variant="card" />
                                <div className="space-y-1.5">
                                    <h1 className="text-2xl font-semibold tracking-tight">Welcome back</h1>
                                    <p className="text-muted-foreground text-sm">Sign in to continue to your workspace.</p>
                                </div>
                            </div>

                            {status && (
                                <div className="bg-accent text-accent-foreground mb-5 rounded-lg px-3 py-2 text-center text-sm font-medium">
                                    {status}
                                </div>
                            )}

                            <form className="space-y-5" onSubmit={submit}>
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <div className="relative">
                                        <Mail
                                            className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                                            aria-hidden="true"
                                        />
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
                                            className="focus-visible:border-primary/40 focus-visible:ring-primary/25 h-11 rounded-xl border bg-white/80 pl-10 dark:bg-white/[0.03]"
                                        />
                                    </div>
                                    <InputError message={errors.email} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password">Password</Label>
                                    <div className="relative">
                                        <Lock
                                            className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                                            aria-hidden="true"
                                        />
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
                                            className="focus-visible:border-primary/40 focus-visible:ring-primary/25 h-11 rounded-xl border bg-white/80 pr-11 pl-10 dark:bg-white/[0.03]"
                                        />
                                        <button
                                            type="button"
                                            tabIndex={3}
                                            className="text-muted-foreground hover:text-foreground absolute top-1/2 right-3.5 -translate-y-1/2 rounded-md p-0.5 transition-colors"
                                            onClick={() => setShowPassword((visible) => !visible)}
                                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                                        >
                                            {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                        </button>
                                    </div>
                                    <InputError message={errors.password} />
                                </div>

                                <div className="flex items-center justify-between gap-3 pt-0.5">
                                    <label
                                        htmlFor="remember"
                                        className="text-foreground/90 flex cursor-pointer items-center gap-2.5 text-sm"
                                    >
                                        <Checkbox
                                            id="remember"
                                            name="remember"
                                            tabIndex={4}
                                            checked={data.remember}
                                            onCheckedChange={(checked) => setData('remember', checked === true)}
                                            disabled={processing}
                                            className="border-primary/30 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                        />
                                        Remember me
                                    </label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={route('password.request')}
                                            className="text-muted-foreground hover:text-primary text-sm no-underline transition-colors"
                                            tabIndex={5}
                                        >
                                            Forgot password?
                                        </TextLink>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    tabIndex={6}
                                    disabled={processing}
                                    className="from-primary to-primary/90 hover:from-primary/95 hover:to-primary/85 focus-visible:ring-primary/30 h-11 w-full rounded-xl bg-gradient-to-r shadow-[0_0.5rem_1.25rem_-0.35rem_color-mix(in_srgb,var(--primary)_55%,transparent)] transition-all hover:-translate-y-px hover:shadow-[0_0.75rem_1.5rem_-0.35rem_color-mix(in_srgb,var(--primary)_50%,transparent)] motion-reduce:hover:translate-y-0"
                                >
                                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                                    {processing ? 'Signing in…' : 'Sign In'}
                                </Button>
                            </form>

                            <p className="text-muted-foreground mt-6 text-center text-xs tracking-wide">
                                Authorized personnel only
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}

function BrandMark() {
    return <BrandLogo variant="login" />;
}
