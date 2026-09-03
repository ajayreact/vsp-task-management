import { PageHeader } from '@/components/admin/page-header';
import { SearchInput } from '@/components/admin/search-input';
import { MyTodoWidget } from '@/components/todos/my-todo-widget';
import { TodoItemRow } from '@/components/todos/todo-item-row';
import { TodoQuickAddDialog } from '@/components/todos/todo-quick-add-dialog';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TaskLayout from '@/layouts/task-layout';
import { type MyTodoSnapshot, type TodoItem, type TodoSection, type TodoUpcomingGroup } from '@/lib/todos';
import { type BreadcrumbItem, type Option } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Props {
    greeting: string;
    filters: {
        tab: string;
        priority: string;
        date: string;
        project: number | null;
        client: number | null;
        search: string;
    };
    sections: {
        today: TodoSection;
        overdue: TodoSection;
        upcoming: { count: number; groups: TodoUpcomingGroup[] };
        completed_today: TodoSection;
    };
    items: TodoItem[];
    counts: {
        all: number;
        today: number;
        overdue: number;
        upcoming: number;
        completed: number;
    };
    priorities: Option[];
    projects: { id: number; name: string }[];
    clients: { id: number; name: string }[];
}

const ALL = 'all';
const TABS = [
    { value: 'all', label: 'All' },
    { value: 'today', label: 'Today' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'completed', label: 'Completed' },
] as const;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Todos', href: '/tasks/todos' },
];

export default function MyTodosIndex({ greeting, filters, sections, items, counts, priorities, projects, clients }: Props) {
    const [addOpen, setAddOpen] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeout = setTimeout(() => apply({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    const apply = (changes: Record<string, string | number | null>) => {
        router.get(
            '/tasks/todos',
            {
                tab: filters.tab || undefined,
                priority: filters.priority || undefined,
                date: filters.date || undefined,
                project: filters.project ?? undefined,
                client: filters.client ?? undefined,
                search: search || undefined,
                ...changes,
            },
            { preserveState: true, replace: true },
        );
    };

    const snapshot: MyTodoSnapshot = {
        greeting,
        today: sections.today,
        overdue: sections.overdue,
        upcoming: sections.upcoming,
        completed_today: sections.completed_today,
        progress: {
            completed: sections.completed_today.count,
            total: sections.today.count + sections.completed_today.count,
            overdue_count: sections.overdue.count,
            due_today_count: sections.today.count,
            completed_today_count: sections.completed_today.count,
        },
        href: '/tasks/todos',
        priorities,
    };

    return (
        <TaskLayout breadcrumbs={breadcrumbs}>
            <Head title="My Todos" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="My Todos"
                    description="Personal reminders and assigned work in one place."
                    action={
                        <Button type="button" onClick={() => setAddOpen(true)}>
                            <Plus />
                            Add Todo
                        </Button>
                    }
                />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-6">
                        <div className="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center">
                            <div className="flex flex-wrap gap-2">
                                {TABS.map((tab) => (
                                    <Button
                                        key={tab.value}
                                        type="button"
                                        size="sm"
                                        variant={filters.tab === tab.value || (tab.value === 'all' && !filters.tab) ? 'default' : 'outline'}
                                        onClick={() => apply({ tab: tab.value })}
                                    >
                                        {tab.label}
                                        <span className="text-muted-foreground ml-1 text-xs">({counts[tab.value === 'completed' ? 'completed' : tab.value]})</span>
                                    </Button>
                                ))}
                            </div>

                            <div className="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <SearchInput value={search} onChange={setSearch} placeholder="Search by title" />
                                <Select value={filters.priority || ALL} onValueChange={(value) => apply({ priority: value === ALL ? '' : value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Priority" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>All priorities</SelectItem>
                                        {priorities.map((priority) => (
                                            <SelectItem key={priority.value} value={priority.value}>
                                                {priority.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.project ? String(filters.project) : ALL} onValueChange={(value) => apply({ project: value === ALL ? null : Number(value) })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Project" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>All projects</SelectItem>
                                        {projects.map((project) => (
                                            <SelectItem key={project.id} value={String(project.id)}>
                                                {project.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.client ? String(filters.client) : ALL} onValueChange={(value) => apply({ client: value === ALL ? null : Number(value) })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Client" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ALL}>All clients</SelectItem>
                                        {clients.map((client) => (
                                            <SelectItem key={client.id} value={String(client.id)}>
                                                {client.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <section className="vsp-card space-y-4 p-4 md:p-5">
                            {items.length === 0 ? (
                                <p className="text-muted-foreground py-8 text-center text-sm">No todos match these filters.</p>
                            ) : (
                                items.map((item) => <TodoItemRow key={item.key} item={item} />)
                            )}
                        </section>
                    </div>

                    <div className="space-y-4">
                        <MyTodoWidget snapshot={snapshot} />
                    </div>
                </div>
            </div>

            <TodoQuickAddDialog open={addOpen} onOpenChange={setAddOpen} priorities={priorities} />
        </TaskLayout>
    );
}
