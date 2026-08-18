import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/hooks/use-initials';
import { useForm } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export interface TaskCommentRow {
    id: number;
    body: string;
    author_name: string;
    author_avatar?: string | null;
    created_at: string | null;
    updated_at: string | null;
    can_edit: boolean;
    can_delete: boolean;
}

function formatted(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export function TaskComments({ taskId, comments, canComment }: { taskId: number; comments: TaskCommentRow[]; canComment: boolean }) {
    const initials = useInitials();
    const form = useForm<{ body: string }>({ body: '' });

    return (
        <Card>
            <CardHeader>
                <CardTitle>Discussion</CardTitle>
                <CardDescription>Notes and updates for everyone working on this task.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {comments.length === 0 && <p className="text-muted-foreground text-sm">No comments yet.</p>}

                <div className="space-y-4">
                    {comments.map((comment) => (
                        <CommentRow key={comment.id} taskId={taskId} comment={comment} initials={initials} />
                    ))}
                </div>

                {canComment && (
                    <form
                        className="space-y-3 rounded-lg border p-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(`/tasks/${taskId}/comments`, {
                                preserveScroll: true,
                                onSuccess: () => form.reset(),
                            });
                        }}
                    >
                        <Textarea
                            value={form.data.body}
                            onChange={(event) => form.setData('body', event.target.value)}
                            placeholder="Add a comment…"
                            rows={3}
                        />
                        <InputError message={form.errors.body} />
                        <Button type="submit" disabled={form.processing || form.data.body.trim() === ''}>
                            Post comment
                        </Button>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

function CommentRow({
    taskId,
    comment,
    initials,
}: {
    taskId: number;
    comment: TaskCommentRow;
    initials: (name: string) => string;
}) {
    const [editing, setEditing] = useState(false);
    const editForm = useForm<{ body: string }>({ body: comment.body });
    const deleteForm = useForm({});

    return (
        <div className="flex gap-3">
            <Avatar className="size-9 shrink-0">
                <AvatarImage src={comment.author_avatar ?? undefined} alt={comment.author_name} />
                <AvatarFallback className="bg-primary/10 text-primary text-xs font-semibold">
                    {initials(comment.author_name)}
                </AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span className="text-sm font-semibold">{comment.author_name}</span>
                    <span className="text-muted-foreground text-xs">
                        {formatted(comment.created_at)}
                        {comment.updated_at && comment.updated_at !== comment.created_at && ' · edited'}
                    </span>
                </div>

                {editing ? (
                    <form
                        className="mt-2 space-y-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            editForm.put(`/tasks/${taskId}/comments/${comment.id}`, {
                                preserveScroll: true,
                                onSuccess: () => setEditing(false),
                            });
                        }}
                    >
                        <Textarea
                            value={editForm.data.body}
                            onChange={(event) => editForm.setData('body', event.target.value)}
                            rows={3}
                        />
                        <InputError message={editForm.errors.body} />
                        <div className="flex gap-2">
                            <Button type="submit" size="sm" disabled={editForm.processing || editForm.data.body.trim() === ''}>
                                Save
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() => {
                                    editForm.setData('body', comment.body);
                                    setEditing(false);
                                }}
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                ) : (
                    <p className="mt-1 text-sm whitespace-pre-wrap">{comment.body}</p>
                )}

                {!editing && (comment.can_edit || comment.can_delete) && (
                    <div className="mt-2 flex gap-1">
                        {comment.can_edit && (
                            <Button type="button" variant="ghost" size="sm" className="h-7 px-2 text-xs" onClick={() => setEditing(true)}>
                                <Pencil className="size-3.5" />
                                Edit
                            </Button>
                        )}
                        {comment.can_delete && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="text-destructive hover:text-destructive h-7 px-2 text-xs"
                                disabled={deleteForm.processing}
                                onClick={() => {
                                    if (window.confirm('Remove this comment?')) {
                                        deleteForm.delete(`/tasks/${taskId}/comments/${comment.id}`, { preserveScroll: true });
                                    }
                                }}
                            >
                                <Trash2 className="size-3.5" />
                                Delete
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
