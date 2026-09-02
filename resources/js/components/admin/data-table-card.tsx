import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface DataTableCardProps {
    title: string;
    description?: string;
    action?: ReactNode;
    toolbar?: ReactNode;
    footer?: ReactNode;
    children: ReactNode;
    className?: string;
}

/**
 * Frozen DataTable shell — VSP card chrome, header, one-line toolbar, table, footer.
 * Do not restyle per page; extend shared tokens in app.css instead.
 */
export function DataTableCard({ title, description, action, toolbar, footer, children, className }: DataTableCardProps) {
    return (
        <section className={cn('data-table-card vsp-card mb-0 min-w-0 max-w-full overflow-hidden bg-white', className)}>
            <div className="flex flex-wrap items-start justify-between gap-4 border-b border-[rgba(120,115,110,0.12)] px-6 py-5">
                <div className="min-w-0 space-y-1.5">
                    <h1 className="text-foreground text-xl font-semibold tracking-tight">{title}</h1>
                    {description && <p className="text-muted-foreground max-w-2xl text-sm leading-relaxed">{description}</p>}
                </div>
                {action && <div className="flex shrink-0 flex-wrap items-center gap-2">{action}</div>}
            </div>

            {toolbar && <div className="flex min-w-0 max-w-full flex-wrap items-center gap-3 px-6 py-4">{toolbar}</div>}

            <div className="min-w-0 max-w-full overflow-x-auto overscroll-x-contain border-t border-[rgba(120,115,110,0.12)]">{children}</div>

            {footer && <div className="border-t border-[rgba(120,115,110,0.12)] px-6 py-4">{footer}</div>}
        </section>
    );
}
