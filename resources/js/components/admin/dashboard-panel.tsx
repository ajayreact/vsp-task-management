import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type ComponentType, type ReactNode } from 'react';

export type PanelTone = 'indigo' | 'sky' | 'emerald' | 'amber' | 'teal' | 'fuchsia';

const TONES: Record<PanelTone, { wash: string; iconWrap: string }> = {
    sky: {
        wash: 'bg-gradient-to-br from-white to-sky-50/90',
        iconWrap: 'bg-gradient-to-br from-sky-500 to-blue-400',
    },
    emerald: {
        wash: 'bg-gradient-to-br from-white to-emerald-50/90',
        iconWrap: 'bg-gradient-to-br from-emerald-600 to-lime-400',
    },
    amber: {
        wash: 'bg-gradient-to-br from-white to-rose-50/90',
        iconWrap: 'bg-gradient-to-br from-rose-600 to-orange-400',
    },
    fuchsia: {
        wash: 'bg-gradient-to-br from-white to-fuchsia-50/80',
        iconWrap: 'bg-gradient-to-br from-violet-600 to-fuchsia-400',
    },
    teal: {
        wash: 'bg-gradient-to-br from-white to-cyan-50/90',
        iconWrap: 'bg-gradient-to-br from-teal-600 to-cyan-400',
    },
    indigo: {
        wash: 'bg-gradient-to-br from-white to-indigo-50/90',
        iconWrap: 'bg-gradient-to-br from-indigo-600 to-violet-400',
    },
};

interface DashboardPanelProps {
    title: string;
    description?: string;
    icon: ComponentType<{ className?: string; strokeWidth?: number }>;
    tone?: PanelTone;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
}

/**
 * Premium dashboard panel — VSP card chrome + soft wash + gradient icon.
 */
export function DashboardPanel({ title, description, icon: Icon, tone = 'indigo', action, children, className }: DashboardPanelProps) {
    const theme = TONES[tone];

    return (
        <section className={cn('vsp-card mb-0 overflow-hidden', theme.wash, className)}>
            <header className="flex items-start justify-between gap-3 border-b border-[rgba(120,115,110,0.12)] px-5 py-4">
                <div className="flex min-w-0 items-start gap-3">
                    <span className={cn('mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full text-white shadow-sm', theme.iconWrap)}>
                        <Icon className="size-5" strokeWidth={1.75} />
                    </span>
                    <div className="min-w-0 space-y-0.5">
                        <h3 className="text-foreground text-base font-semibold tracking-tight">{title}</h3>
                        {description && <p className="text-muted-foreground text-sm">{description}</p>}
                    </div>
                </div>
                {action}
            </header>
            <div className="px-5 py-4">{children}</div>
        </section>
    );
}

interface PanelRowProps {
    href: string;
    title: string;
    meta: string;
    badge?: ReactNode;
}

export function PanelRow({ href, title, meta, badge }: PanelRowProps) {
    return (
        <Link
            href={href}
            className="group/row flex items-start justify-between gap-3 rounded-xl border border-[rgba(120,115,110,0.14)] bg-white/80 px-3.5 py-3 text-sm shadow-[0_0.0625rem_0.25rem_0_rgba(38,43,67,0.06)] transition-all duration-300 hover:-translate-y-px hover:border-[rgba(120,115,110,0.28)] hover:shadow-[var(--vsp-card-shadow)]"
        >
            <div className="min-w-0">
                <div className="text-foreground group-hover/row:text-primary font-medium transition-colors">{title}</div>
                <div className="text-muted-foreground mt-0.5 text-xs">{meta}</div>
            </div>
            {badge}
        </Link>
    );
}

export function PanelEmpty({ children }: { children: ReactNode }) {
    return (
        <div className="rounded-xl border border-dashed border-[rgba(120,115,110,0.22)] bg-white/50 px-4 py-8 text-center">
            <p className="text-muted-foreground text-sm">{children}</p>
        </div>
    );
}
