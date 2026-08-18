import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Search } from 'lucide-react';
import { type ComponentProps } from 'react';

type SearchInputProps = Omit<ComponentProps<'input'>, 'type'> & {
    containerClassName?: string;
};

/**
 * Visual search field for list toolbars. Callers still own debounce/router logic.
 */
export function SearchInput({ className, containerClassName, ...props }: SearchInputProps) {
    return (
        <div className={cn('relative w-full max-w-xs', containerClassName)}>
            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" strokeWidth={1.75} />
            <Input type="search" className={cn('bg-card h-10 pl-9', className)} {...props} />
        </div>
    );
}
