import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ArrowUp, CheckCircle2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

export interface CourseModule {
    anchor: string;
    title: string;
    number: number;
    lesson_ids: number[];
}

/** Space taken by the sticky app header and a one-row module tab bar. */
export const STICKY_OFFSET = 120;

const APP_HEADER_HEIGHT = 64;

/** Space taken by the sticky app header and the tab bar, which wraps onto more rows on wide screens. */
export function stickyOffset(): number {
    const bar = document.querySelector<HTMLElement>('nav[aria-label="Course modules"]');

    return bar ? APP_HEADER_HEIGHT + bar.offsetHeight + 16 : STICKY_OFFSET;
}

export function scrollToAnchor(anchor: string, behavior: ScrollBehavior = 'smooth') {
    const element = document.getElementById(anchor);

    if (!element) {
        return false;
    }

    const top = element.getBoundingClientRect().top + window.scrollY - stickyOffset() + 8;
    window.scrollTo({ top: Math.max(0, top), behavior });

    return true;
}

/**
 * Which of the given anchors is being read: the last one whose top has passed
 * the sticky header. Recomputed on scroll at most once per frame.
 */
export function useScrollSpy(anchors: string[], offset?: number): string | null {
    const [active, setActive] = useState<string | null>(anchors[0] ?? null);
    const key = anchors.join('|');

    useEffect(() => {
        const ids = key === '' ? [] : key.split('|');
        let frame = 0;

        const measure = () => {
            frame = 0;
            let current = ids[0] ?? null;
            const line = offset ?? stickyOffset();
            const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4;

            for (const id of ids) {
                const element = document.getElementById(id);

                if (element && element.getBoundingClientRect().top - line <= 16) {
                    current = id;
                }
            }

            if (atBottom && ids.length > 0 && window.scrollY > 0) {
                current = ids[ids.length - 1];
            }

            setActive(current);
        };

        const onScroll = () => {
            if (frame === 0) {
                frame = window.requestAnimationFrame(measure);
            }
        };

        measure();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);

        return () => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);

            if (frame !== 0) {
                window.cancelAnimationFrame(frame);
            }
        };
    }, [key, offset]);

    return active;
}

/**
 * The course's module tabs. Sticky below the app header; on narrow screens
 * only this bar scrolls sideways. Clicking a tab scrolls the page to that
 * module and records it in the address bar, so Back and Forward move between
 * modules.
 */
export function ModuleTabs({
    modules,
    active,
    progress,
    onSelect,
}: {
    modules: CourseModule[];
    active: string | null;
    progress: Record<string, { completed: number; counted: number }>;
    onSelect: (anchor: string) => void;
}) {
    const barRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const bar = barRef.current;
        const tab = active ? bar?.querySelector<HTMLElement>(`[data-anchor="${active}"]`) : null;

        if (!bar || !tab) {
            return;
        }

        const left = tab.offsetLeft - bar.clientWidth / 2 + tab.clientWidth / 2;
        bar.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
    }, [active]);

    return (
        <nav aria-label="Course modules" className="bg-background/95 sticky top-16 z-10 border-b backdrop-blur-sm">
            <div ref={barRef} className="overflow-x-auto overscroll-x-contain [scrollbar-width:thin]" data-testid="module-tab-bar">
                <ol className="flex w-max min-w-full gap-x-1 px-4 md:px-6 lg:w-full lg:min-w-0 lg:flex-wrap">
                    {modules.map((module) => {
                        const isActive = module.anchor === active;
                        const counts = progress[module.anchor];
                        const done = counts && counts.counted > 0 && counts.completed >= counts.counted;

                        return (
                            <li key={module.anchor}>
                                <a
                                    href={`#${module.anchor}`}
                                    data-anchor={module.anchor}
                                    aria-current={isActive ? 'location' : undefined}
                                    onClick={(event) => {
                                        event.preventDefault();
                                        onSelect(module.anchor);
                                    }}
                                    className={cn(
                                        'flex h-10 items-center gap-1.5 border-b-2 px-2.5 text-sm whitespace-nowrap transition-colors',
                                        isActive
                                            ? 'border-emerald-600 font-semibold text-emerald-700 dark:text-emerald-300'
                                            : 'text-muted-foreground hover:text-foreground border-transparent',
                                    )}
                                >
                                    {done ? (
                                        <CheckCircle2 className="size-4 text-emerald-600" aria-hidden />
                                    ) : (
                                        <span
                                            className={cn(
                                                'flex size-5 items-center justify-center rounded-full text-[11px] tabular-nums',
                                                isActive ? 'bg-emerald-600 text-white' : 'bg-muted',
                                            )}
                                            aria-hidden
                                        >
                                            {module.number}
                                        </span>
                                    )}
                                    <span>{module.title}</span>
                                    {counts && (
                                        <span className="text-muted-foreground text-xs font-normal tabular-nums">
                                            {counts.completed}/{counts.counted}
                                        </span>
                                    )}
                                </a>
                            </li>
                        );
                    })}
                </ol>
            </div>
        </nav>
    );
}

/** A small "Back to modules" button once the recruiter has scrolled well into the course. */
export function BackToModulesButton({ threshold = 900 }: { threshold?: number }) {
    const [visible, setVisible] = useState(false);

    const update = useCallback(() => setVisible(window.scrollY > threshold), [threshold]);

    useEffect(() => {
        update();
        window.addEventListener('scroll', update, { passive: true });

        return () => window.removeEventListener('scroll', update);
    }, [update]);

    if (!visible) {
        return null;
    }

    return (
        <Button
            type="button"
            variant="outline"
            size="sm"
            className="bg-background/95 fixed right-4 bottom-4 z-20 shadow-md md:right-6 md:bottom-6"
            onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
        >
            <ArrowUp /> Back to modules
        </Button>
    );
}
