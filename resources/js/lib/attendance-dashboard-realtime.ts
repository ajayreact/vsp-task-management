type AttendanceDashboardListener = () => void;

const listeners = new Set<AttendanceDashboardListener>();

export function subscribeToAttendanceDashboardEvents(listener: AttendanceDashboardListener): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function dispatchAttendanceDashboardUpdate(): void {
    listeners.forEach((listener) => listener());
}

export function handleAttendanceDashboardBroadcast(eventName: string): void {
    if (eventName === 'attendance-dashboard.updated') {
        dispatchAttendanceDashboardUpdate();
    }
}
