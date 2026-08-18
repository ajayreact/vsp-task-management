import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const OPTIONS = [7, 10, 15, 25, 50] as const;

/**
 * Materialize-style "Show N entries" control for DataTable toolbars.
 */
export function EntriesSelect({ value, onChange }: { value: number; onChange: (perPage: number) => void }) {
    const current = OPTIONS.includes(value as (typeof OPTIONS)[number]) ? value : 15;

    return (
        <div className="text-muted-foreground flex items-center gap-2 text-sm whitespace-nowrap">
            <span>Show</span>
            <Select value={String(current)} onValueChange={(next) => onChange(Number(next))}>
                <SelectTrigger className="bg-card h-9 w-[4.75rem]" aria-label="Entries per page">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {OPTIONS.map((option) => (
                        <SelectItem key={option} value={String(option)}>
                            {option}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <span>entries</span>
        </div>
    );
}
