import { useEffect, useState } from 'react';

interface NetWorkingDurationOptions {
    checkInAt: string | null;
    totalBreakSeconds: number;
    activeBreakStartedAt: string | null;
    isActive: boolean;
    frozenNetSeconds: number | null;
}

export function useNetWorkingDuration({
    checkInAt,
    totalBreakSeconds,
    activeBreakStartedAt,
    isActive,
    frozenNetSeconds,
}: NetWorkingDurationOptions): number {
    const [elapsedSeconds, setElapsedSeconds] = useState(0);

    useEffect(() => {
        if (frozenNetSeconds !== null) {
            setElapsedSeconds(frozenNetSeconds);

            return;
        }

        if (!checkInAt || !isActive) {
            setElapsedSeconds(0);

            return;
        }

        const startedAt = new Date(checkInAt).getTime();
        const completedBreakSeconds = totalBreakSeconds;
        const activeBreakStart = activeBreakStartedAt ? new Date(activeBreakStartedAt).getTime() : null;

        const tick = () => {
            const now = Date.now();
            const grossSeconds = Math.max(0, Math.floor((now - startedAt) / 1000));
            const activeBreakSeconds =
                activeBreakStart !== null ? Math.max(0, Math.floor((now - activeBreakStart) / 1000)) : 0;

            setElapsedSeconds(Math.max(0, grossSeconds - completedBreakSeconds - activeBreakSeconds));
        };

        tick();
        const interval = window.setInterval(tick, 1000);

        return () => window.clearInterval(interval);
    }, [checkInAt, totalBreakSeconds, activeBreakStartedAt, isActive, frozenNetSeconds]);

    return elapsedSeconds;
}

export function useBreakDuration(activeBreakStartedAt: string | null, isOnBreak: boolean): number {
    const [elapsedSeconds, setElapsedSeconds] = useState(0);

    useEffect(() => {
        if (!activeBreakStartedAt || !isOnBreak) {
            setElapsedSeconds(0);

            return;
        }

        const startedAt = new Date(activeBreakStartedAt).getTime();

        const tick = () => {
            setElapsedSeconds(Math.max(0, Math.floor((Date.now() - startedAt) / 1000)));
        };

        tick();
        const interval = window.setInterval(tick, 1000);

        return () => window.clearInterval(interval);
    }, [activeBreakStartedAt, isOnBreak]);

    return elapsedSeconds;
}
