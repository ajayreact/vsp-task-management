import { type ReactNode } from 'react';

export function PageHeader({ title, description, action }: { title: string; description?: string; action?: ReactNode }) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight text-foreground">{title}</h1>
                {description && <p className="text-muted-foreground max-w-2xl text-sm leading-relaxed">{description}</p>}
            </div>
            {action}
        </div>
    );
}
