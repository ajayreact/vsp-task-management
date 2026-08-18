import { PageHeader } from '@/components/admin/page-header';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { Lock, Palette, Settings, User } from 'lucide-react';
import { type ComponentType, type ReactNode } from 'react';

const sidebarNavItems: {
    title: string;
    url: string;
    description: string;
    icon: ComponentType<{ className?: string; strokeWidth?: number }>;
}[] = [
    {
        title: 'Profile',
        url: '/settings/profile',
        description: 'Name, email, and account',
        icon: User,
    },
    {
        title: 'Password',
        url: '/settings/password',
        description: 'Login credentials',
        icon: Lock,
    },
    {
        title: 'Appearance',
        url: '/settings/appearance',
        description: 'Theme and display',
        icon: Palette,
    },
];

export default function SettingsLayout({ children }: { children: ReactNode }) {
    const { url } = usePage();

    return (
        <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Settings"
                description="Manage your profile, security, and how VSP looks on your device."
            />

            <div className="grid gap-6 xl:grid-cols-[17.5rem_minmax(0,1fr)]">
                <aside className="vsp-card mb-0 h-fit overflow-hidden bg-gradient-to-br from-white to-indigo-50/50 p-2">
                    <div className="flex items-center gap-2 px-3 py-2.5">
                        <span className="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                            <Settings className="size-4" strokeWidth={1.75} />
                        </span>
                        <div>
                            <p className="text-sm font-semibold">Account</p>
                            <p className="text-muted-foreground text-xs">Personal preferences</p>
                        </div>
                    </div>

                    <nav className="space-y-1 p-1.5" aria-label="Settings">
                        {sidebarNavItems.map((item) => {
                            const active = url === item.url || url.startsWith(`${item.url}/`);
                            const Icon = item.icon;

                            return (
                                <Link
                                    key={item.url}
                                    href={item.url}
                                    prefetch
                                    className={cn(
                                        'flex items-start gap-3 rounded-xl px-3 py-2.5 transition-all duration-200',
                                        active
                                            ? 'bg-primary text-primary-foreground shadow-sm'
                                            : 'text-foreground hover:bg-white/80 hover:shadow-[0_0.0625rem_0.25rem_0_rgba(38,43,67,0.06)]',
                                    )}
                                >
                                    <Icon className={cn('mt-0.5 size-4 shrink-0', active ? 'text-primary-foreground' : 'text-primary')} strokeWidth={1.75} />
                                    <span className="min-w-0">
                                        <span className="block text-sm font-medium">{item.title}</span>
                                        <span
                                            className={cn(
                                                'block text-xs leading-snug',
                                                active ? 'text-primary-foreground/80' : 'text-muted-foreground',
                                            )}
                                        >
                                            {item.description}
                                        </span>
                                    </span>
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <div className="min-w-0 space-y-6 xl:max-w-3xl">{children}</div>
            </div>
        </div>
    );
}
