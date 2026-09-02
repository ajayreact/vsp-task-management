import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

export type DraftChecklistItem = {
    key: string;
    title: string;
};

function newItem(): DraftChecklistItem {
    return { key: crypto.randomUUID(), title: '' };
}

export function TaskCreateChecklist({
    items,
    onChange,
    errors,
}: {
    items: DraftChecklistItem[];
    onChange: (items: DraftChecklistItem[]) => void;
    errors?: Record<string, string | undefined>;
}) {
    const [draftTitle, setDraftTitle] = useState('');

    const addItem = () => {
        const title = draftTitle.trim();

        if (title === '') {
            return;
        }

        onChange([...items, { key: crypto.randomUUID(), title }]);
        setDraftTitle('');
    };

    const updateItem = (key: string, title: string) => {
        onChange(items.map((item) => (item.key === key ? { ...item, title } : item)));
    };

    const removeItem = (key: string) => {
        onChange(items.filter((item) => item.key !== key));
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Checklist</CardTitle>
                <CardDescription>Add steps to complete before or during the task. Empty items are ignored.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {items.length === 0 && <p className="text-muted-foreground text-sm">No checklist items yet.</p>}

                <div className="space-y-2">
                    {items.map((item, index) => (
                        <div key={item.key} className="flex items-start gap-2 rounded-lg border p-3">
                            <Checkbox checked={false} disabled aria-hidden className="mt-0.5" />
                            <div className="min-w-0 flex-1">
                                <Input
                                    value={item.title}
                                    onChange={(event) => updateItem(item.key, event.target.value)}
                                    aria-label={`Checklist item ${index + 1}`}
                                />
                                <InputError message={errors?.[`checklist.${index}.title`]} />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="text-destructive hover:text-destructive shrink-0"
                                onClick={() => removeItem(item.key)}
                                aria-label="Remove checklist item"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ))}
                </div>

                <div className="flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-end">
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="create_checklist_title">Add checklist item</Label>
                        <Input
                            id="create_checklist_title"
                            value={draftTitle}
                            onChange={(event) => setDraftTitle(event.target.value)}
                            placeholder="Check spelling and grammar, review design…"
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    addItem();
                                }
                            }}
                        />
                    </div>
                    <Button type="button" variant="outline" onClick={addItem} disabled={draftTitle.trim() === ''}>
                        <Plus className="mr-2 size-4" />
                        Add checklist item
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

export function sanitizeChecklistItems(items: DraftChecklistItem[]): { title: string }[] {
    return items
        .map((item) => ({ title: item.title.trim() }))
        .filter((item) => item.title !== '');
}
