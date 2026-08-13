import { Button } from '@/components/ui/button';
import { type Paginated } from '@/types';
import { Link } from '@inertiajs/react';

export function Pagination<T>({ page }: { page: Paginated<T> }) {
    if (page.last_page <= 1) {
        return (
            <p className="text-muted-foreground text-sm">
                {page.total} {page.total === 1 ? 'record' : 'records'}
            </p>
        );
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-muted-foreground text-sm">
                Showing {page.from}–{page.to} of {page.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {page.links.map((link, index) => (
                    <Button
                        key={index}
                        variant={link.active ? 'default' : 'outline'}
                        size="sm"
                        disabled={link.url === null}
                        asChild={link.url !== null}
                    >
                        {link.url !== null ? (
                            // Labels arrive from Laravel as "&laquo; Previous" and similar.
                            <Link href={link.url} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} />
                        ) : (
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        )}
                    </Button>
                ))}
            </div>
        </div>
    );
}
