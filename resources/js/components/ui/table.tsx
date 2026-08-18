import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Table primitives for use inside DataTableCard. The card owns elevation;
 * this wrapper only handles horizontal scroll.
 */
const Table = React.forwardRef<HTMLTableElement, React.HTMLAttributes<HTMLTableElement>>(({ className, ...props }, ref) => (
    <div className="relative w-full overflow-auto">
        <table ref={ref} className={cn('w-full caption-bottom text-sm', className)} {...props} />
    </div>
));
Table.displayName = 'Table';

const TableHeader = React.forwardRef<HTMLTableSectionElement, React.HTMLAttributes<HTMLTableSectionElement>>(
    ({ className, ...props }, ref) => (
        <thead
            ref={ref}
            className={cn('data-table-header [&_tr]:border-border [&_tr]:border-b', className)}
            style={{ backgroundColor: 'var(--table-header-bg-color, #f5f5f7)' }}
            {...props}
        />
    ),
);
TableHeader.displayName = 'TableHeader';

const TableBody = React.forwardRef<HTMLTableSectionElement, React.HTMLAttributes<HTMLTableSectionElement>>(
    ({ className, ...props }, ref) => <tbody ref={ref} className={cn('[&_tr:last-child]:border-0', className)} {...props} />,
);
TableBody.displayName = 'TableBody';

const TableFooter = React.forwardRef<HTMLTableSectionElement, React.HTMLAttributes<HTMLTableSectionElement>>(
    ({ className, ...props }, ref) => (
        <tfoot ref={ref} className={cn('bg-muted/40 border-t font-medium [&>tr]:last:border-b-0', className)} {...props} />
    ),
);
TableFooter.displayName = 'TableFooter';

const TableRow = React.forwardRef<HTMLTableRowElement, React.HTMLAttributes<HTMLTableRowElement>>(({ className, ...props }, ref) => (
    <tr
        ref={ref}
        className={cn(
            'border-border bg-card border-b transition-colors hover:bg-[#f8f8fc] data-[state=selected]:bg-muted',
            className,
        )}
        {...props}
    />
));
TableRow.displayName = 'TableRow';

const TableHead = React.forwardRef<HTMLTableCellElement, React.ThHTMLAttributes<HTMLTableCellElement>>(
    ({ className, style, ...props }, ref) => (
        <th
            ref={ref}
            className={cn(
                'text-muted-foreground h-11 px-6 text-left align-middle text-[11px] font-semibold tracking-[0.1em] uppercase [&:has([role=checkbox])]:pr-0',
                className,
            )}
            style={{ backgroundColor: 'var(--table-header-bg-color, #f5f5f7)', ...style }}
            {...props}
        />
    ),
);
TableHead.displayName = 'TableHead';

const TableCell = React.forwardRef<HTMLTableCellElement, React.TdHTMLAttributes<HTMLTableCellElement>>(
    ({ className, ...props }, ref) => (
        <td ref={ref} className={cn('h-[4.25rem] px-6 py-4 align-middle [&:has([role=checkbox])]:pr-0', className)} {...props} />
    ),
);
TableCell.displayName = 'TableCell';

const TableCaption = React.forwardRef<HTMLTableCaptionElement, React.HTMLAttributes<HTMLTableCaptionElement>>(
    ({ className, ...props }, ref) => <caption ref={ref} className={cn('text-muted-foreground mt-4 text-sm', className)} {...props} />,
);
TableCaption.displayName = 'TableCaption';

export { Table, TableHeader, TableBody, TableFooter, TableHead, TableRow, TableCell, TableCaption };
