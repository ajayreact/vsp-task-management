import { cn } from '@/lib/utils';
import { BookOpenText, Headphones } from 'lucide-react';
import { useCallback, useState } from 'react';

export type ListenMode = 'read' | 'listen';

const STORAGE_KEY = 'recruiter-training-listen-mode';

function readStoredMode(): ListenMode {
    try {
        return window.localStorage.getItem(STORAGE_KEY) === 'listen' ? 'listen' : 'read';
    } catch {
        return 'read';
    }
}

/** Read + Listen (text and audio) or Listen only (audio, text hidden), remembered on this device. */
export function useListenMode(): [ListenMode, (mode: ListenMode) => void] {
    const [mode, setMode] = useState<ListenMode>(readStoredMode);

    const choose = useCallback((next: ListenMode) => {
        setMode(next);

        try {
            window.localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // Storage can be unavailable (private mode); the choice then lasts for this page only.
        }
    }, []);

    return [mode, choose];
}

const OPTIONS: { value: ListenMode; label: string; icon: typeof Headphones }[] = [
    { value: 'read', label: 'Read + Listen', icon: BookOpenText },
    { value: 'listen', label: 'Listen only', icon: Headphones },
];

export function ListenModeToggle({ value, onChange, className }: { value: ListenMode; onChange: (mode: ListenMode) => void; className?: string }) {
    return (
        <div
            role="radiogroup"
            aria-label="How to take this lesson"
            className={cn('bg-muted inline-flex rounded-lg p-0.5', className)}
            data-testid="listen-mode"
        >
            {OPTIONS.map(({ value: option, label, icon: Icon }) => (
                <button
                    key={option}
                    type="button"
                    role="radio"
                    aria-checked={value === option}
                    onClick={() => onChange(option)}
                    className={cn(
                        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
                        value === option ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    <Icon className="size-3.5" aria-hidden />
                    {label}
                </button>
            ))}
        </div>
    );
}

export function ListenOnlyNotice() {
    return (
        <p className="text-muted-foreground rounded-lg border border-dashed px-3 py-2 text-sm" role="status" data-testid="listen-only-notice">
            Listen only: the lesson text is hidden. Switch to Read + Listen to see it. Press “Mark Lesson Complete” when you have finished.
        </p>
    );
}
