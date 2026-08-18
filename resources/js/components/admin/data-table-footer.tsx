import { DataTableExportButtons, buildExportQuery, exportHref } from '@/components/admin/data-table-export';
import { EntriesSelect } from '@/components/admin/entries-select';
import { Pagination } from '@/components/admin/pagination';
import { type Paginated } from '@/types';

interface DataTableFooterProps<T> {
    page: Paginated<T>;
    onPerPageChange: (perPage: number) => void;
    /** Base path ending in /export, e.g. /tasks/export */
    exportBasePath: string;
    exportParams?: Record<string, string | number | null | undefined>;
}

/**
 * Frozen DataTable footer: Show entries | Excel + Pdf | Showing X to Y (+ pages).
 */
export function DataTableFooter<T>({ page, onPerPageChange, exportBasePath, exportParams = {} }: DataTableFooterProps<T>) {
    const query = buildExportQuery(exportParams);

    return (
        <Pagination
            page={page}
            leading={<EntriesSelect value={page.per_page} onChange={onPerPageChange} />}
            actions={
                <DataTableExportButtons
                    excelHref={exportHref(exportBasePath, 'excel', query)}
                    pdfHref={exportHref(exportBasePath, 'pdf', query)}
                />
            }
        />
    );
}
