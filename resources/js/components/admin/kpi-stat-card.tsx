import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type ComponentType, type ReactNode } from 'react';

export type KpiTone = 'indigo' | 'sky' | 'emerald' | 'amber' | 'teal' | 'fuchsia';

const TONES: Record<
    KpiTone,
    {
        card: string;
        iconWrap: string;
    }
> = {
    sky: {
        card: 'bg-gradient-to-br from-white to-sky-100/80',
        iconWrap: 'bg-gradient-to-br from-sky-500 to-blue-400',
    },
    emerald: {
        card: 'bg-gradient-to-br from-white to-emerald-100/80',
        iconWrap: 'bg-gradient-to-br from-emerald-600 to-lime-400',
    },
    amber: {
        card: 'bg-gradient-to-br from-white to-rose-100/80',
        iconWrap: 'bg-gradient-to-br from-rose-600 to-orange-400',
    },
    fuchsia: {
        card: 'bg-gradient-to-br from-white to-fuchsia-100/70',
        iconWrap: 'bg-gradient-to-br from-violet-600 to-fuchsia-400',
    },
    teal: {
        card: 'bg-gradient-to-br from-white to-cyan-100/80',
        iconWrap: 'bg-gradient-to-br from-teal-600 to-cyan-400',
    },
    indigo: {
        card: 'bg-gradient-to-br from-white to-indigo-100/80',
        iconWrap: 'bg-gradient-to-br from-indigo-600 to-violet-400',
    },
};

interface KpiStatCardProps {
    href: string;
    label: string;
    value: ReactNode;
    icon: ComponentType<{ className?: string; strokeWidth?: number }>;
    tone?: KpiTone;
    footer?: ReactNode;
    className?: string;
}

/**
 * Soft pastel KPI tile using shared --vsp-card-* tokens.
 */
export function KpiStatCard({ href, label, value, icon: Icon, tone = 'indigo', footer, className }: KpiStatCardProps) {
    const theme = TONES[tone];

    return (
        <Link href={href} className={cn('group mb-0 block h-full', className)}>
            <article className={cn('vsp-card relative mb-0 flex h-full items-center gap-4 overflow-hidden px-5 py-5', theme.card)}>
                <div className="min-w-0 flex-1 space-y-2">
                    <p className="text-muted-foreground text-sm font-medium">{label}</p>
                    <p className="text-foreground text-[1.75rem] leading-none font-bold tracking-tight tabular-nums sm:text-[2rem]">{value}</p>
                    {footer && <div className="pt-0.5">{footer}</div>}
                </div>

                <span className={cn('flex size-14 shrink-0 items-center justify-center rounded-full text-white shadow-sm', theme.iconWrap)}>
                    <Icon className="size-6" strokeWidth={1.75} />
                </span>
            </article>
        </Link>
    );
}
