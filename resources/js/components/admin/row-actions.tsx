import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button, buttonVariants } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useState } from 'react';

export interface RowActionItem {
    key: string;
    label: string;
    href?: string;
    onSelect?: () => void;
    destructive?: boolean;
    /** When set, opens a confirm dialog instead of navigating immediately. */
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
 * Run after the dropdown has finished dismissing so Radix can restore body
 * pointer-events before another modal (Dialog / AlertDialog) mounts.
 */
function afterMenuClose(action?: () => void): void {
    if (!action) {
        return;
    }

    window.setTimeout(action, 0);
}

/**
 * Compact three-dot menu for row actions. Prefer this when a row has more than one action.
 *
 * Confirm dialogs are siblings of the menu (not nested inside it) to avoid the Radix
 * DropdownMenu + AlertDialog composition bug that leaves the page unclickable.
 */
export function RowActions({ label, items, className }: RowActionsProps) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [confirmItem, setConfirmItem] = useState<RowActionItem | null>(null);

    if (items.length === 0) {
        return null;
    }

    const confirm = confirmItem?.confirm;

    return (
        <>
            <DropdownMenu
                open={menuOpen}
                onOpenChange={(open) => {
                    setMenuOpen(open);

                    // Safety net: if a prior Dialog/AlertDialog race left body locked, unlock it.
                    if (!open) {
                        window.requestAnimationFrame(() => {
                            if (document.body.style.pointerEvents === 'none') {
                                document.body.style.pointerEvents = '';
                            }
                        });
                    }
                }}
            >
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
                                <DropdownMenuItem
                                    key={item.key}
                                    className="text-destructive focus:text-destructive cursor-pointer"
                                    onSelect={() => {
                                        afterMenuClose(() => setConfirmItem(item));
                                    }}
                                >
                                    {item.label}
                                </DropdownMenuItem>
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
                                className={
                                    item.destructive
                                        ? 'text-destructive focus:text-destructive cursor-pointer'
                                        : 'cursor-pointer'
                                }
                                onSelect={() => {
                                    afterMenuClose(item.onSelect);
                                }}
                            >
                                {item.label}
                            </DropdownMenuItem>
                        );
                    })}
                </DropdownMenuContent>
            </DropdownMenu>

            <AlertDialog
                open={confirm !== undefined && confirmItem !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setConfirmItem(null);
                        window.requestAnimationFrame(() => {
                            if (document.body.style.pointerEvents === 'none') {
                                document.body.style.pointerEvents = '';
                            }
                        });
                    }
                }}
            >
                {confirm && (
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>{confirm.title}</AlertDialogTitle>
                            <AlertDialogDescription>{confirm.description}</AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                className={cn(buttonVariants({ variant: 'destructive' }))}
                                onClick={() => {
                                    router.delete(confirm.url, { preserveScroll: true });
                                    setConfirmItem(null);
                                }}
                            >
                                Delete
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                )}
            </AlertDialog>
        </>
    );
}
