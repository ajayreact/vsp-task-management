import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface AttendanceTableScrollProps {
    children: ReactNode;
    className?: string;
}

/**
 * Keeps wide attendance tables scrollable without expanding the page layout.
 */
export function AttendanceTableScroll({ children, className }: AttendanceTableScrollProps) {
    return (
        <div className={cn('w-full max-w-full min-w-0 overflow-x-auto overscroll-x-contain', className)}>{children}</div>
    );
}

export const attendanceTableClassName = 'min-w-max caption-bottom text-sm';
