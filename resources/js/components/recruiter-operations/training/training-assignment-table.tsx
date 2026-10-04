import { RowActions } from '@/components/admin/row-actions';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { TrainingProgressBar, TrainingStatusBadge, formatTrainingDate } from './training-ui';

export interface TrainingAssignmentRow {
    id: number;
    recruiter_name: string | null;
    employee_code: string | null;
    course: { id: number; title: string };
    category: string | null;
    version: string;
    status: string;
    status_label: string;
    progress_percent: number;
    lessons_completed: number;
    lessons_counted: number;
    assigned_at: string;
    due_at: string | null;
    completed_at: string | null;
    assigned_by: string | null;
    can: { delete: boolean };
}

/**
 * Learning progress per assignment. No scores, no rankings.
 */
export function TrainingAssignmentTable({
    rows,
    withActions = false,
    emptyMessage,
}: {
    rows: TrainingAssignmentRow[];
    withActions?: boolean;
    emptyMessage: string;
}) {
    const columns = withActions ? 10 : 9;

    return (
        <Table className="min-w-max">
            <TableHeader>
                <TableRow>
                    <TableHead>Recruiter</TableHead>
                    <TableHead>Course</TableHead>
                    <TableHead>Version</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="w-48">Progress</TableHead>
                    <TableHead>Assigned</TableHead>
                    <TableHead>Due</TableHead>
                    <TableHead>Completed</TableHead>
                    <TableHead>Assigned by</TableHead>
                    {withActions && (
                        <TableHead className="w-12">
                            <span className="sr-only">Actions</span>
                        </TableHead>
                    )}
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.length === 0 && (
                    <TableRow>
                        <TableCell colSpan={columns} className="text-muted-foreground py-10 text-center">
                            {emptyMessage}
                        </TableCell>
                    </TableRow>
                )}

                {rows.map((row) => (
                    <TableRow key={row.id}>
                        <TableCell className="text-sm">
                            <div className="font-medium">{row.recruiter_name ?? '—'}</div>
                            {row.employee_code && <div className="text-muted-foreground text-xs">{row.employee_code}</div>}
                        </TableCell>
                        <TableCell className="text-sm">
                            <div className="font-medium">{row.course.title}</div>
                            {row.category && <div className="text-muted-foreground text-xs">{row.category}</div>}
                        </TableCell>
                        <TableCell className="text-sm">{row.version}</TableCell>
                        <TableCell>
                            <TrainingStatusBadge status={row.status} label={row.status_label} />
                        </TableCell>
                        <TableCell>
                            <TrainingProgressBar percent={row.progress_percent} label={`Progress for ${row.recruiter_name ?? 'recruiter'}`} />
                            <div className="text-muted-foreground mt-0.5 text-xs">
                                {row.lessons_completed}/{row.lessons_counted} lessons
                            </div>
                        </TableCell>
                        <TableCell className="text-sm whitespace-nowrap">{formatTrainingDate(row.assigned_at)}</TableCell>
                        <TableCell
                            className={
                                row.status === 'overdue' ? 'text-destructive text-sm font-medium whitespace-nowrap' : 'text-sm whitespace-nowrap'
                            }
                        >
                            {formatTrainingDate(row.due_at)}
                        </TableCell>
                        <TableCell className="text-sm whitespace-nowrap">{formatTrainingDate(row.completed_at)}</TableCell>
                        <TableCell className="text-muted-foreground text-sm">{row.assigned_by ?? '—'}</TableCell>
                        {withActions && (
                            <TableCell>
                                <RowActions
                                    label={`Actions for ${row.course.title}`}
                                    items={
                                        row.can.delete
                                            ? [
                                                  {
                                                      key: 'withdraw',
                                                      label: 'Withdraw Assignment',
                                                      confirm: {
                                                          url: `/recruiter/training/assignments/${row.id}`,
                                                          title: 'Withdraw training assignment?',
                                                          description: `This will remove the training assignment from ${row.recruiter_name ?? 'this recruiter'}.${
                                                              row.lessons_completed > 0 || row.status !== 'assigned'
                                                                  ? `\n\nThey have already started it: their progress on this course (${row.lessons_completed}/${row.lessons_counted} lessons) will be removed. Quiz attempts are kept.`
                                                                  : ''
                                                          }\n\nThe training course, lessons, versions, and other recruiters' assignments will not be deleted.`,
                                                          confirmLabel: 'Withdraw Assignment',
                                                      },
                                                  },
                                              ]
                                            : []
                                    }
                                />
                            </TableCell>
                        )}
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
