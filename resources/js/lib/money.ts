/** Format a number as Indian Rupees (₹) with en-IN grouping. */
export function formatInr(amount: number | string | null | undefined): string {
    const value = typeof amount === 'string' ? Number(amount) : (amount ?? 0);

    if (!Number.isFinite(value)) {
        return '₹0.00';
    }

    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}
