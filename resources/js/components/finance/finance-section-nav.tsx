import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

const items = [
    { key: 'dashboard', label: 'Dashboard', href: '/admin/finance' },
    { key: 'income', label: 'My Income', href: '/admin/finance/income' },
    { key: 'expenses', label: 'My Expenses', href: '/admin/finance/expenses' },
    { key: 'loans', label: 'My Loans', href: '/admin/finance/loans' },
] as const;

export type FinanceSection = (typeof items)[number]['key'];

export function FinanceSectionNav({ active }: { active: FinanceSection }) {
    return (
        <nav className="flex flex-wrap gap-2" aria-label="My Finance sections">
            {items.map((item) => {
                const isActive = item.key === active;

                return (
                    <Link
                        key={item.key}
                        href={item.href}
                        className={cn(
                            'rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors',
                            isActive
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border/80 bg-white text-muted-foreground hover:border-primary/40 hover:text-foreground',
                        )}
                        aria-current={isActive ? 'page' : undefined}
                    >
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}
