import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

/**
 * A confirmed POST action such as Publish or Archive.
 */
export function ConfirmPost({
    trigger,
    title,
    description,
    url,
    confirmLabel,
    destructive = false,
}: {
    trigger: ReactNode;
    title: string;
    description: string;
    url: string;
    confirmLabel: string;
    destructive?: boolean;
}) {
    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>{description}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        className={cn(buttonVariants({ variant: destructive ? 'destructive' : 'default' }))}
                        onClick={() => router.post(url, {}, { preserveScroll: true })}
                    >
                        {confirmLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

type Tone = 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'outline';

const ASSIGNMENT_TONE: Record<string, Tone> = {
    assigned: 'warning',
    in_progress: 'info',
    completed: 'success',
    overdue: 'danger',
};

const CONTENT_TONE: Record<string, Tone> = {
    draft: 'warning',
    published: 'success',
    archived: 'outline',
};

export function TrainingStatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge variant={ASSIGNMENT_TONE[status] ?? 'neutral'}>{label}</Badge>;
}

export function ContentStatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge variant={CONTENT_TONE[status] ?? 'neutral'}>{label}</Badge>;
}

export function RequiredBadge({ required }: { required: boolean }) {
    return required ? <Badge variant="neutral">Required</Badge> : <Badge variant="outline">Optional</Badge>;
}

export function TrainingProgressBar({ percent, className, label }: { percent: number; className?: string; label?: string }) {
    const value = Math.max(0, Math.min(100, Math.round(percent)));

    return (
        <div className={cn('flex items-center gap-3', className)}>
            <div
                className="bg-muted h-2 flex-1 overflow-hidden rounded-full"
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={value}
                aria-label={label ?? 'Progress'}
            >
                <div className="h-full rounded-full bg-emerald-500 transition-all" style={{ width: `${value}%` }} />
            </div>
            <span className="text-muted-foreground w-10 text-right text-xs tabular-nums">{value}%</span>
        </div>
    );
}

export function formatTrainingDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

export function formatMinutes(minutes: number | null): string {
    if (!minutes) {
        return '—';
    }

    if (minutes < 60) {
        return `${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
}

/**
 * Training's own tabs. Hidden tabs are also refused by the server.
 */
export function TrainingSubNav() {
    const { can } = usePermissions();
    const { url, props } = usePage<{ trainingLearner?: boolean }>();
    const path = url.split('?')[0];

    const tabs = [
        { label: 'Overview', href: '/recruiter/training', exact: true, show: true },
        { label: 'My Training', href: '/recruiter/training/my-training', show: props.trainingLearner === true },
        { label: 'Team Progress', href: '/recruiter/training/team', show: can('recruiter.team.view') },
        { label: 'Assignments', href: '/recruiter/training/assignments', show: can('recruiter.training.assign') },
        { label: 'Manage', href: '/recruiter/training/manage', show: can('recruiter.training.manage') },
    ].filter((tab) => tab.show);

    return (
        <nav className="flex flex-wrap gap-1 border-b border-[rgba(120,115,110,0.14)]" aria-label="Training sections">
            {tabs.map((tab) => {
                const active = tab.exact ? path === tab.href : path === tab.href || path.startsWith(`${tab.href}/`);

                return (
                    <Link
                        key={tab.href}
                        href={tab.href}
                        className={cn(
                            '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                            active ? 'text-foreground border-emerald-600' : 'text-muted-foreground hover:text-foreground border-transparent',
                        )}
                        aria-current={active ? 'page' : undefined}
                    >
                        {tab.label}
                    </Link>
                );
            })}
        </nav>
    );
}
