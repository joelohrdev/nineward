import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type Column<Row> = {
    key: string;
    header: string;
    cell: (row: Row) => ReactNode;
    className?: string;
    /** The column that titles each stacked entry on small screens. */
    primary?: boolean;
};

type Props<Row> = {
    columns: Column<Row>[];
    rows: Row[];
    rowKey: (row: Row) => string | number;
    numbered?: boolean;
    caption?: string;
};

/**
 * A ruled scorebook table. On narrow screens each row becomes a stacked
 * entry instead of a table that scrolls sideways.
 */
export function DataTable<Row>({
    columns,
    rows,
    rowKey,
    numbered = false,
    caption,
}: Props<Row>) {
    return (
        <table className="w-full border-collapse text-[0.9375rem] tabular-nums">
            {caption && <caption className="sr-only">{caption}</caption>}
            <thead className="sticky top-0 z-10 hidden bg-paper md:table-header-group">
                <tr className="border-b-2 border-ink">
                    {numbered && (
                        <th scope="col" className="w-10 py-2 pr-2 text-left">
                            <span className="sr-only">Row</span>
                        </th>
                    )}
                    {columns.map((column) => (
                        <th
                            key={column.key}
                            scope="col"
                            className={cn(
                                'px-3 py-2 text-left label-caps text-ink-muted',
                                column.className,
                            )}
                        >
                            {column.header}
                        </th>
                    ))}
                </tr>
            </thead>
            <tbody>
                {rows.map((row, index) => (
                    <tr
                        key={rowKey(row)}
                        className={cn(
                            'relative flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-rule py-3 md:table-row md:py-0 md:hover:bg-wash/60',
                            numbered && 'pl-10 md:pl-0',
                        )}
                    >
                        {numbered && (
                            <td className="absolute top-3 left-0 w-10 pr-2 align-middle font-display text-lg text-ink-muted md:static md:py-2.5">
                                {index + 1}
                            </td>
                        )}
                        {columns.map((column) => (
                            <td
                                key={column.key}
                                className={cn(
                                    'md:px-3 md:py-2.5',
                                    column.primary
                                        ? 'order-first basis-full font-semibold md:basis-auto'
                                        : 'inline-flex items-center text-sm text-ink-muted md:table-cell md:text-[0.9375rem] md:text-ink',
                                    column.className,
                                )}
                            >
                                {!column.primary && (
                                    <span className="mr-1.5 label-caps text-[0.6875rem] text-ink-muted md:hidden">
                                        {column.header}
                                    </span>
                                )}
                                {column.cell(row)}
                            </td>
                        ))}
                    </tr>
                ))}
            </tbody>
        </table>
    );
}
