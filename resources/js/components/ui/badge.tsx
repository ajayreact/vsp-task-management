import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-hidden focus:ring-2 focus:ring-ring focus:ring-offset-2',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary text-primary-foreground',
                secondary: 'border-transparent bg-secondary text-secondary-foreground',
                destructive: 'border-transparent bg-destructive/10 text-destructive',
                outline: 'text-foreground',
                success: 'border-transparent bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
                warning: 'border-transparent bg-amber-500/10 text-amber-700 dark:text-amber-400',
                danger: 'border-transparent bg-red-500/10 text-red-700 dark:text-red-400',
                info: 'border-transparent bg-primary/10 text-primary',
                neutral: 'border-transparent bg-slate-500/10 text-slate-600 dark:text-slate-300',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

export interface BadgeProps extends React.HTMLAttributes<HTMLDivElement>, VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
    return <div className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export { Badge, badgeVariants };
