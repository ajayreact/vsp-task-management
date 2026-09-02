import { type Option } from '@/types';

export interface TodoChecklistProgress {
    completed: number;
    total: number;
}

export interface TodoSubtaskItem {
    key: string;
    title: string;
    href: string;
}

export interface TodoItem {
    key: string;
    source: 'personal' | 'task' | 'subtask';
    id: number;
    task_id: number | null;
    parent_task_id: number | null;
    title: string;
    subtitle: string | null;
    client_name: string | null;
    project_id: number | null;
    client_id: number | null;
    priority: string;
    priority_label: string;
    priority_weight: number;
    due_at: string | null;
    due_date: string | null;
    due_time: string | null;
    is_overdue: boolean;
    is_due_today: boolean;
    is_completed: boolean;
    completed_at: string | null;
    href: string;
    kind_label: string;
    checklist: TodoChecklistProgress | null;
    subtasks: TodoSubtaskItem[];
    can_complete: boolean;
    can_move_to_today: boolean;
    note: string | null;
}

export interface TodoSection {
    count: number;
    items: TodoItem[];
}

export interface TodoUpcomingGroup {
    label: string;
    count: number;
    items: TodoItem[];
}

export interface TodoProgress {
    completed: number;
    total: number;
    overdue_count: number;
    due_today_count: number;
    completed_today_count: number;
}

export interface MyTodoSnapshot {
    greeting: string;
    today: TodoSection;
    overdue: TodoSection;
    upcoming: {
        count: number;
        groups: TodoUpcomingGroup[];
    };
    completed_today: TodoSection;
    progress: TodoProgress;
    href: string;
    priorities: Option[];
}

export function formatTodoDue(item: Pick<TodoItem, 'due_at' | 'due_time' | 'due_date'>): string | null {
    if (!item.due_at && !item.due_date) {
        return null;
    }

    const due = item.due_at ? new Date(item.due_at) : new Date(`${item.due_date}T${item.due_time ?? '23:59:00'}`);

    if (item.due_time) {
        return due.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    }

    return due.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
}

export function priorityClass(priority: string): string {
    switch (priority) {
        case 'urgent':
            return 'border-transparent bg-red-500/10 text-red-700 dark:text-red-400';
        case 'high':
            return 'border-transparent bg-amber-500/10 text-amber-700 dark:text-amber-400';
        case 'low':
            return 'text-muted-foreground';
        default:
            return '';
    }
}
