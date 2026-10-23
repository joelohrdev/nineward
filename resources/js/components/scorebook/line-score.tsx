type Entry = {
    label: string;
    value: number | null;
};

/**
 * One row of counts styled like a scoreboard line score.
 */
export function LineScore({ entries }: { entries: Entry[] }) {
    return (
        <dl className="grid overflow-hidden rounded-sm border-2 border-board bg-board text-board-ink sm:grid-cols-[repeat(auto-fit,minmax(8rem,1fr))]">
            {entries.map((entry) => (
                <div
                    key={entry.label}
                    className="flex items-center justify-between gap-4 border-b border-board-rule px-4 py-2.5 last:border-b-0 sm:flex-col-reverse sm:items-start sm:justify-start sm:gap-2 sm:border-r sm:border-b-0 sm:py-3 sm:last:border-r-0"
                >
                    <dt className="label-caps text-board-ink/70">
                        {entry.label}
                    </dt>
                    <dd className="font-display text-3xl leading-none text-bulb tabular-nums sm:text-5xl">
                        {entry.value ?? '–'}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
