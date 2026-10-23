import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type LineupEntry = {
    id: number;
    primary: string;
    secondary?: string;
    leading?: ReactNode;
};

type Props = {
    title: string;
    count?: number;
    entries: LineupEntry[];
    selectedIds: number[];
    onToggle: (id: number) => void;
    empty: string;
};

/**
 * A selectable list styled as a lineup card, used in pairs by the roster
 * builder.
 */
export function LineupCard({
    title,
    count,
    entries,
    selectedIds,
    onToggle,
    empty,
}: Props) {
    return (
        <section className="flex min-w-0 flex-col border-2 border-ink bg-sheet">
            <header className="flex items-baseline justify-between border-b-2 border-ink px-3 py-2">
                <h2 className="label-caps text-ink">{title}</h2>
                <span className="font-display text-lg leading-none tabular-nums">
                    {count ?? entries.length}
                </span>
            </header>
            {entries.length === 0 ? (
                <p className="px-3 py-8 text-center text-sm text-ink-muted">
                    {empty}
                </p>
            ) : (
                <ul className="divide-y divide-rule">
                    {entries.map((entry, index) => {
                        const isSelected = selectedIds.includes(entry.id);

                        return (
                            <li key={entry.id}>
                                <button
                                    type="button"
                                    aria-pressed={isSelected}
                                    onClick={() => onToggle(entry.id)}
                                    className={cn(
                                        'grid min-h-12 w-full grid-cols-[2.25rem_auto_1fr] items-center gap-3 border-l-4 py-1.5 pr-3 text-left transition-colors',
                                        isSelected
                                            ? 'border-l-brand bg-wash'
                                            : 'border-l-transparent hover:bg-wash/60',
                                    )}
                                >
                                    <span className="text-center font-display text-base text-ink-muted">
                                        {index + 1}
                                    </span>
                                    <span>{entry.leading}</span>
                                    <span className="min-w-0">
                                        <span className="block truncate font-semibold">
                                            {entry.primary}
                                        </span>
                                        {entry.secondary && (
                                            <span className="block truncate text-sm text-ink-muted">
                                                {entry.secondary}
                                            </span>
                                        )}
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
