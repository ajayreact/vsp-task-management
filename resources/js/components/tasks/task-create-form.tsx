import InputError from '@/components/input-error';
import {
    TaskCreateAttachments,
    attachmentFiles,
    type DraftAttachment,
} from '@/components/tasks/task-create-attachments';
import {
    TaskCreateChecklist,
    sanitizeChecklistItems,
    type DraftChecklistItem,
} from '@/components/tasks/task-create-checklist';
import {
    TaskCreateSubtasks,
    sanitizeSubtasks,
    type DraftSubtask,
} from '@/components/tasks/task-create-subtasks';
import { TaskDetailsCard, TaskRequirementCard, ASSIGNMENT_OPEN_BOARD, type TaskFormOptions, type TaskFormValues } from '@/components/tasks/task-form';
import { Button } from '@/components/ui/button';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEvent, useState } from 'react';

type TaskCreateFormData = TaskFormValues & {
    publish_to_open_board: boolean;
    checklist: { title: string }[];
    subtasks: ReturnType<typeof sanitizeSubtasks>;
    files: File[];
};

export function TaskCreateForm({
    options,
    initial,
    submitLabel,
    cancelUrl,
    showAssignee = false,
    canManageChecklist = false,
    canManageSubtasks = false,
    canAttachFiles = true,
}: {
    options: TaskFormOptions;
    initial: TaskFormValues;
    submitLabel: string;
    cancelUrl: string;
    showAssignee?: boolean;
    canManageChecklist?: boolean;
    canManageSubtasks?: boolean;
    canAttachFiles?: boolean;
}) {
    const [checklistItems, setChecklistItems] = useState<DraftChecklistItem[]>([]);
    const [subtaskItems, setSubtaskItems] = useState<DraftSubtask[]>([]);
    const [attachmentItems, setAttachmentItems] = useState<DraftAttachment[]>([]);

    const form = useForm<TaskCreateFormData>({
        ...initial,
        publish_to_open_board: false,
        checklist: [],
        subtasks: [],
        files: [],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const publishToOpenBoard = form.data.assigned_employee_id === ASSIGNMENT_OPEN_BOARD;
        const assignedEmployeeId =
            publishToOpenBoard || form.data.assigned_employee_id === '' ? '' : form.data.assigned_employee_id;

        const payload: TaskCreateFormData = {
            ...form.data,
            assigned_employee_id: assignedEmployeeId,
            publish_to_open_board: publishToOpenBoard,
            checklist: canManageChecklist ? sanitizeChecklistItems(checklistItems) : [],
            subtasks: canManageSubtasks ? sanitizeSubtasks(subtaskItems) : [],
            files: canAttachFiles ? attachmentFiles(attachmentItems) : [],
        };

        form.transform(() => payload);
        form.post('/tasks', {
            forceFormData: payload.files.length > 0,
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <TaskDetailsCard
                data={form.data}
                setData={(key, value) => form.setData(key, value)}
                errors={form.errors}
                options={options}
                showAssignee={showAssignee}
                cardDescription={
                    showAssignee
                        ? 'Describe the work and choose Open Board, assign someone directly, or save as a draft for later.'
                        : 'Describe the work. The task starts as a draft until it is assigned or published.'
                }
            />

            <TaskRequirementCard
                value={form.data.requirement}
                onChange={(value) => form.setData('requirement', value)}
                error={form.errors.requirement}
            />

            {canManageChecklist && (
                <TaskCreateChecklist items={checklistItems} onChange={setChecklistItems} errors={form.errors} />
            )}

            {canManageSubtasks && (
                <TaskCreateSubtasks
                    items={subtaskItems}
                    onChange={setSubtaskItems}
                    employees={options.assignableEmployees ?? []}
                    errors={form.errors}
                />
            )}

            {canAttachFiles && (
                <TaskCreateAttachments items={attachmentItems} onChange={setAttachmentItems} errors={form.errors} />
            )}

            <InputError message={form.errors.checklist as string | undefined} />
            <InputError message={form.errors.subtasks as string | undefined} />

            <div className="flex gap-2">
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <LoaderCircle className="animate-spin" />}
                    {submitLabel}
                </Button>
                <Button type="button" variant="outline" asChild>
                    <Link href={cancelUrl}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
