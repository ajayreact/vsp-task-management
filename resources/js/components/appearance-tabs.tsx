import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Check, LucideIcon, Monitor, Moon, Sun } from 'lucide-react';

type ThemeOption = {
    value: Appearance;
    icon: LucideIcon;
    label: string;
    description: string;
    preview: 'light' | 'dark' | 'system';
};

const OPTIONS: ThemeOption[] = [
    {
        value: 'light',
        icon: Sun,
        label: 'Light',
        description: 'Bright workspace with crisp contrast.',
        preview: 'light',
    },
    {
        value: 'dark',
        icon: Moon,
        label: 'Dark',
        description: 'Reduced glare for low-light sessions.',
        preview: 'dark',
    },
    {
        value: 'system',
        icon: Monitor,
        label: 'System',
        description: 'Follow your device appearance setting.',
        preview: 'system',
    },
];

function ThemePreview({ variant }: { variant: ThemeOption['preview'] }) {
    return (
        <div
            className={cn(
                'relative h-20 w-full overflow-hidden rounded-lg border shadow-[0_0.0625rem_0.25rem_0_rgba(38,43,67,0.06)]',
                variant === 'dark' ? 'border-slate-700 bg-slate-900' : 'border-[rgba(120,115,110,0.18)] bg-white',
            )}
        >
            <div
                className={cn(
                    'absolute inset-x-2 top-2 h-2 rounded-full',
                    variant === 'dark' ? 'bg-slate-700' : variant === 'system' ? 'bg-gradient-to-r from-slate-200 to-slate-700' : 'bg-slate-100',
                )}
            />
            <div className="absolute inset-x-2 top-6 grid grid-cols-3 gap-1.5">
                <div className={cn('h-8 rounded-md', variant === 'dark' ? 'bg-indigo-500/70' : 'bg-indigo-100')} />
                <div className={cn('h-8 rounded-md', variant === 'dark' ? 'bg-slate-800' : 'bg-slate-50')} />
                <div className={cn('h-8 rounded-md', variant === 'dark' ? 'bg-slate-800' : 'bg-slate-50')} />
            </div>
            <div
                className={cn(
                    'absolute right-2 bottom-2 left-2 h-2 rounded-full',
                    variant === 'dark' ? 'bg-slate-700' : 'bg-slate-100',
                )}
            />
        </div>
    );
}

export default function AppearanceTabs() {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <div className="grid gap-3 sm:grid-cols-3">
            {OPTIONS.map((option) => {
                const selected = appearance === option.value;
                const Icon = option.icon;

                return (
                    <button
                        key={option.value}
                        type="button"
                        onClick={() => updateAppearance(option.value)}
                        className={cn(
                            'vsp-card group mb-0 flex h-full flex-col gap-3 rounded-xl p-4 text-left transition-all duration-200',
                            selected
                                ? 'ring-primary bg-gradient-to-br from-indigo-50/90 to-white ring-2'
                                : 'hover:-translate-y-0.5 hover:shadow-[var(--vsp-card-shadow-hover)]',
                        )}
                        aria-pressed={selected}
                    >
                        <ThemePreview variant={option.preview} />
                        <div className="flex items-start justify-between gap-2">
                            <div className="flex items-start gap-2.5">
                                <span
                                    className={cn(
                                        'flex size-8 items-center justify-center rounded-full text-white shadow-sm',
                                        selected
                                            ? 'bg-gradient-to-br from-indigo-600 to-violet-400'
                                            : 'bg-gradient-to-br from-slate-500 to-slate-400 group-hover:from-indigo-600 group-hover:to-violet-400',
                                    )}
                                >
                                    <Icon className="size-4" strokeWidth={1.75} />
                                </span>
                                <span>
                                    <span className="text-foreground block text-sm font-semibold">{option.label}</span>
                                    <span className="text-muted-foreground mt-0.5 block text-xs leading-relaxed">{option.description}</span>
                                </span>
                            </div>
                            {selected && (
                                <span className="bg-primary text-primary-foreground flex size-5 shrink-0 items-center justify-center rounded-full">
                                    <Check className="size-3" strokeWidth={2.5} />
                                </span>
                            )}
                        </div>
                    </button>
                );
            })}
        </div>
    );
}
