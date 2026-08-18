import { useCallback, useEffect, useState } from 'react';
import { subscribeToOpenBoardEvents, type OpenBoardTask } from '@/lib/open-board-realtime';

type BoardFilters = {
    department: number | null;
    mine_only: boolean;
};

function taskMatchesFilters(task: OpenBoardTask, filters: BoardFilters, employeeDepartmentId: number | null): boolean {
    if (filters.department !== null && task.department_id !== filters.department) {
        return false;
    }

    if (filters.mine_only && employeeDepartmentId !== null && task.department_id !== employeeDepartmentId) {
        return false;
    }

    return true;
}

export function useOpenBoardRealtime({
    initialTasks,
    employeeDepartmentId,
    filters,
}: {
    initialTasks: OpenBoardTask[];
    employeeDepartmentId: number | null;
    filters: BoardFilters;
}): OpenBoardTask[] {
    const [tasks, setTasks] = useState(initialTasks);

    useEffect(() => {
        setTasks(initialTasks);
    }, [initialTasks]);

    const onClaimed = useCallback((taskId: number) => {
        setTasks((current) => current.filter((task) => task.id !== taskId));
    }, []);

    const onPublished = useCallback(
        (task: OpenBoardTask) => {
            if (!taskMatchesFilters(task, filters, employeeDepartmentId)) {
                return;
            }

            setTasks((current) => {
                if (current.some((row) => row.id === task.id)) {
                    return current;
                }

                return [task, ...current];
            });
        },
        [employeeDepartmentId, filters.department, filters.mine_only],
    );

    useEffect(() => {
        const unsubscribe = subscribeToOpenBoardEvents((event) => {
            if (event.type === 'claimed') {
                onClaimed(event.taskId);

                return;
            }

            onPublished(event.task);
        });

        return unsubscribe;
    }, [onClaimed, onPublished]);

    return tasks;
}
