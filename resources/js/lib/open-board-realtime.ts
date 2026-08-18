export type OpenBoardTask = {
    id: number;
    title: string;
    type: string;
    priority: string;
    priority_label: string;
    project_name: string;
    department_id: number | null;
    department_name: string | null;
    estimated_hours: string | null;
    due_at: string | null;
};

export type OpenBoardRealtimeEvent =
    | { type: 'claimed'; taskId: number }
    | { type: 'published'; task: OpenBoardTask };

type OpenBoardListener = (event: OpenBoardRealtimeEvent) => void;

const listeners = new Set<OpenBoardListener>();

export function subscribeToOpenBoardEvents(listener: OpenBoardListener): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function dispatchOpenBoardEvent(event: OpenBoardRealtimeEvent): void {
    listeners.forEach((listener) => listener(event));
}

export function handleOpenBoardBroadcast(eventName: string, payload: Record<string, unknown>): void {
    if (eventName === 'open-board.task-claimed' && typeof payload.task_id === 'number') {
        dispatchOpenBoardEvent({ type: 'claimed', taskId: payload.task_id });

        return;
    }

    if (eventName === 'open-board.task-published' && payload.task && typeof payload.task === 'object') {
        dispatchOpenBoardEvent({ type: 'published', task: payload.task as OpenBoardTask });
    }
}
