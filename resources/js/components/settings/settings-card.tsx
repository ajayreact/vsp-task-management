import { cn } from '@/lib/utils';
import { type ComponentType, type ReactNode } from 'react';

export type SettingsTone = 'indigo' | 'sky' | 'amber' | 'fuchsia';

const TONES: Record<SettingsTone, { wash: string; iconWrap: string }> = {
    indigo: {
        wash: 'bg-gradient-to-br from-white to-indigo-50/90',
        iconWrap: 'bg-gradient-to-br from-indigo-600 to-violet-400',
    },
    sky: {
        wash: 'bg-gradient-to-br from-white to-sky-50/90',
        iconWrap: 'bg-gradient-to-br from-sky-500 to-blue-400',
    },
    amber: {
        wash: 'bg-gradient-to-br from-white to-rose-50/90',
        iconWrap: 'bg-gradient-to-br from-rose-600 to-orange-400',
    },
    fuchsia: {
        wash: 'bg-gradient-to-br from-white to-fuchsia-50/80',
        iconWrap: 'bg-gradient-to-br from-violet-600 to-fuchsia-400',
    },
};

interface SettingsCardProps {
    title: string;
    description?: string;
    icon: ComponentType<{ className?: string; strokeWidth?: number }>;
    tone?: SettingsTone;
    children: ReactNode;
    footer?: ReactNode;
    className?: string;
}

export function SettingsCard({
    title,
    description,
    icon: Icon,
    tone = 'indigo',
    children,
    footer,
    className,
}: SettingsCardProps) {
    const theme = TONES[tone];

    return (
        <section className={cn('vsp-card mb-0 overflow-hidden', theme.wash, className)}>
            <header className="flex items-start gap-3 border-b border-[rgba(120,115,110,0.12)] px-5 py-4">
                <span className={cn('mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full text-white shadow-sm', theme.iconWrap)}>
                    <Icon className="size-5" strokeWidth={1.75} />
                </span>
                <div className="min-w-0 space-y-0.5">
                    <h2 className="text-foreground text-base font-semibold tracking-tight">{title}</h2>
                    {description && <p className="text-muted-foreground text-sm leading-relaxed">{description}</p>}
                </div>
            </header>
            <div className="px-5 py-5">{children}</div>
            {footer && <footer className="border-t border-[rgba(120,115,110,0.12)] bg-white/40 px-5 py-4">{footer}</footer>}
        </section>
    );
}

export function SettingsFormFooter({ children }: { children: ReactNode }) {
    return <div className="flex flex-wrap items-center gap-3">{children}</div>;
}
