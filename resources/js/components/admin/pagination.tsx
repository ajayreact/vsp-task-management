import { cn } from '@/lib/utils';
import { type Paginated } from '@/types';
import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

/**
 * List footer: Show entries (left), optional actions (center), range + pages (right).
 */
export function Pagination<T>({
    page,
    leading,
    actions,
    className,
}: {
    page: Paginated<T>;
    leading?: ReactNode;
    actions?: ReactNode;
    className?: string;
}) {
    const summary =
        page.total === 0
            ? 'No records'
            : page.from !== null && page.to !== null
              ? `Showing ${page.from} to ${page.to} of ${page.total} entries`
              : `${page.total} ${page.total === 1 ? 'record' : 'records'}`;

    return (
        <div className={cn('flex flex-wrap items-center gap-3', className)}>
            <div className="min-w-0 shrink-0">{leading}</div>

            {actions && <div className="flex flex-1 flex-wrap items-center justify-center gap-2">{actions}</div>}

            <div className={cn('flex flex-wrap items-center justify-end gap-3', !actions && 'ml-auto')}>
                <p className="text-muted-foreground text-sm whitespace-nowrap">{summary}</p>

                {page.last_page > 1 && (
                    <nav className="flex flex-wrap items-center gap-1.5" aria-label="Pagination">
                        {page.links.map((link, index) => {
                            const label = stripHtml(link.label);
                            const isEdge = isEdgeLabel(label);
                            const base =
                                'inline-flex h-9 min-w-9 items-center justify-center rounded-full px-2.5 text-sm transition-colors';

                            if (link.url === null) {
                                return (
                                    <span
                                        key={index}
                                        className={cn(base, 'text-muted-foreground/50 cursor-default', isEdge && 'px-3')}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                );
                            }

                            return (
                                <Link
                                    key={index}
                                    href={link.url}
                                    preserveScroll
                                    className={cn(
                                        base,
                                        isEdge && 'px-3',
                                        link.active
                                            ? 'bg-primary text-primary-foreground font-medium'
                                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                    )}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </nav>
                )}
            </div>
        </div>
    );
}

function stripHtml(value: string): string {
    return value.replace(/<[^>]*>/g, '').trim();
}

function isEdgeLabel(label: string): boolean {
    const normalized = label.toLowerCase();

    return (
        normalized.includes('previous') ||
        normalized.includes('next') ||
        normalized.includes('first') ||
        normalized.includes('last') ||
        normalized === '«' ||
        normalized === '»' ||
        normalized === '&laquo;' ||
        normalized === '&raquo;'
    );
}
