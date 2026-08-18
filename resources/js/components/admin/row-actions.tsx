import { ConfirmDelete } from '@/components/admin/confirm-delete';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';

export interface RowActionItem {
    key: string;
    label: string;
    href?: string;
    onSelect?: () => void;
    destructive?: boolean;
    /** When set, opens ConfirmDelete instead of navigating immediately. */
    confirm?: {
        url: string;
        title: string;
        description: string;
    };
}

interface RowActionsProps {
    label: string;
    items: RowActionItem[];
    className?: string;
}

/**
 * Compact three-dot menu for row actions. Prefer this when a row has more than one action.
 */
export function RowActions({ label, items, className }: RowActionsProps) {
    if (items.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className={cn('text-muted-foreground hover:text-foreground size-8', className)}
                    aria-label={label}
                >
                    <MoreHorizontal className="size-4" strokeWidth={1.75} />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                {items.map((item) => {
                    if (item.confirm) {
                        return (
                            <ConfirmDelete
                                key={item.key}
                                url={item.confirm.url}
                                title={item.confirm.title}
                                description={item.confirm.description}
                                trigger={
                                    <DropdownMenuItem
                                        onSelect={(event) => event.preventDefault()}
                                        className="text-destructive focus:text-destructive cursor-pointer"
                                    >
                                        {item.label}
                                    </DropdownMenuItem>
                                }
                            />
                        );
                    }

                    if (item.href) {
                        return (
                            <DropdownMenuItem
                                key={item.key}
                                asChild
                                className={item.destructive ? 'text-destructive focus:text-destructive' : undefined}
                            >
                                <Link href={item.href}>{item.label}</Link>
                            </DropdownMenuItem>
                        );
                    }

                    return (
                        <DropdownMenuItem
                            key={item.key}
                            onSelect={item.onSelect}
                            className={item.destructive ? 'text-destructive focus:text-destructive cursor-pointer' : 'cursor-pointer'}
                        >
                            {item.label}
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
