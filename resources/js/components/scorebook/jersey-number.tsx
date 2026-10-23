import { cn } from '@/lib/utils';

export function JerseyNumber({
    number,
    className,
}: {
    number: number | null;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex size-9 shrink-0 items-center justify-center rounded-sm border border-ink/80 font-display text-xl leading-none tabular-nums',
                number === null &&
                    'border-dashed border-rule-strong text-ink-muted',
                className,
            )}
        >
            {number ?? '–'}
            <span className="sr-only">
                {number === null ? 'No jersey number' : `Jersey ${number}`}
            </span>
        </span>
    );
}
