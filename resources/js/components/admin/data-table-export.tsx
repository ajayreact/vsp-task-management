import { Button } from '@/components/ui/button';

type ExportFormat = 'excel' | 'pdf';

/**
 * Center footer export actions used by every DataTable.
 */
export function DataTableExportButtons({ excelHref, pdfHref }: { excelHref: string; pdfHref: string }) {
    return (
        <>
            <Button variant="outline" size="sm" asChild>
                <a href={excelHref}>Excel</a>
            </Button>
            <Button variant="outline" size="sm" asChild>
                <a href={pdfHref}>Pdf</a>
            </Button>
        </>
    );
}

/**
 * Build a query string for export URLs from the active list filters.
 */
export function buildExportQuery(params: Record<string, string | number | null | undefined>): string {
    const entries = Object.entries(params)
        .filter(([, value]) => value !== null && value !== undefined && value !== '')
        .map(([key, value]) => [key, String(value)] as [string, string]);

    return new URLSearchParams(entries).toString();
}

export function exportHref(basePath: string, format: ExportFormat, query: string): string {
    return `${basePath}/${format}${query ? `?${query}` : ''}`;
}
