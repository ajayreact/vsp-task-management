import { DashboardPanel } from '@/components/admin/dashboard-panel';
import { TodoItemRow } from '@/components/todos/todo-item-row';
import { TodoQuickAddDialog } from '@/components/todos/todo-quick-add-dialog';
import { Button } from '@/components/ui/button';
import { type MyTodoSnapshot } from '@/lib/todos';
import { Link } from '@inertiajs/react';
import { CalendarCheck2, Plus } from 'lucide-react';
import { useState } from 'react';

interface MyTodoWidgetProps {
    snapshot: MyTodoSnapshot;
}

export function MyTodoWidget({ snapshot }: MyTodoWidgetProps) {
    const [addOpen, setAddOpen] = useState(false);
    const progressPercent = snapshot.progress.total > 0
        ? Math.round((snapshot.progress.completed / snapshot.progress.total) * 100)
        : 0;

    const visibleItems = snapshot.today.items.length > 0
        ? snapshot.today.items
        : snapshot.overdue.items.slice(0, 3);

    return (
        <>
            <DashboardPanel
                title="My Today"
                description={snapshot.greeting}
                icon={CalendarCheck2}
                tone="emerald"
                action={
                    <Button type="button" size="sm" variant="outline" onClick={() => setAddOpen(true)}>
                        <Plus className="size-4" />
                        Add
                    </Button>
                }
            >
                <div className="space-y-4 px-1 pb-1">
                    <div className="space-y-2">
                        <div className="flex items-center justify-between gap-3 text-sm">
                            <span className="font-medium">Today&apos;s progress</span>
                            <span className="text-muted-foreground text-xs">
                                {snapshot.progress.completed} / {snapshot.progress.total} completed
                            </span>
                        </div>
                        <div className="bg-muted h-2 overflow-hidden rounded-full">
                            <div
                                className="bg-emerald-500 h-full rounded-full transition-all"
                                style={{ width: `${progressPercent}%` }}
                            />
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3 text-xs">
                        <StatPill label="Overdue" count={snapshot.progress.overdue_count} tone="danger" />
                        <StatPill label="Due today" count={snapshot.progress.due_today_count} />
                        <StatPill label="Completed today" count={snapshot.progress.completed_today_count} tone="success" />
                    </div>

                    <div className="space-y-1">
                        <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                            {snapshot.today.count > 0 ? `${snapshot.today.count} items today` : 'Nothing scheduled for today'}
                        </p>
                        {visibleItems.length === 0 ? (
                            <p className="text-muted-foreground py-4 text-sm">Add a quick todo or check upcoming work below.</p>
                        ) : (
                            visibleItems.map((item) => <TodoItemRow key={item.key} item={item} compact showKind />)
                        )}
                    </div>

                    {snapshot.overdue.count > 0 && (
                        <div className="space-y-1 border-t pt-3">
                            <p className="text-destructive text-xs font-medium tracking-wide uppercase">
                                Overdue · {snapshot.overdue.count}
                            </p>
                            {snapshot.overdue.items.map((item) => (
                                <TodoItemRow key={item.key} item={item} compact showKind={false} />
                            ))}
                        </div>
                    )}

                    {snapshot.upcoming.count > 0 && (
                        <div className="space-y-2 border-t pt-3">
                            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                Upcoming · {snapshot.upcoming.count}
                            </p>
                            {snapshot.upcoming.groups.map((group) => (
                                <div key={group.label} className="space-y-1">
                                    <p className="text-muted-foreground text-xs">{group.label} · {group.count}</p>
                                    {group.items.map((item) => (
                                        <TodoItemRow key={item.key} item={item} compact showKind={false} />
                                    ))}
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="border-t pt-3">
                        <Button asChild variant="ghost" size="sm" className="w-full justify-center">
                            <Link href={snapshot.href}>View all my todos</Link>
                        </Button>
                    </div>
                </div>
            </DashboardPanel>

            <TodoQuickAddDialog open={addOpen} onOpenChange={setAddOpen} priorities={snapshot.priorities} />
        </>
    );
}

function StatPill({ label, count, tone = 'neutral' }: { label: string; count: number; tone?: 'neutral' | 'danger' | 'success' }) {
    return (
        <span
            className={
                tone === 'danger'
                    ? 'text-destructive'
                    : tone === 'success'
                      ? 'text-emerald-700 dark:text-emerald-400'
                      : 'text-muted-foreground'
            }
        >
            {label}: {count}
        </span>
    );
}
