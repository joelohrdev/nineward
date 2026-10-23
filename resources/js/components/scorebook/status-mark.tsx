import { cn } from '@/lib/utils';

export type StatusTone = 'done' | 'open' | 'flagged' | 'failed' | 'neutral';

const toneClasses: Record<StatusTone, { mark: string; text: string }> = {
    done: { mark: 'bg-grass border-grass', text: 'text-grass' },
    open: { mark: 'border-ink bg-transparent', text: 'text-ink' },
    flagged: { mark: 'border-warn bg-warn rotate-45', text: 'text-ink' },
    failed: { mark: 'border-danger bg-danger', text: 'text-danger' },
    neutral: {
        mark: 'border-rule-strong bg-rule-strong',
        text: 'text-ink-muted',
    },
};

/**
 * A status label that never relies on color alone: every tone has its own
 * mark shape as well as its own text.
 */
export function StatusMark({
    tone,
    children,
    className,
}: {
    tone: StatusTone;
    children: string;
    className?: string;
}) {
    const classes = toneClasses[tone];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 label-caps whitespace-nowrap',
                classes.text,
                className,
            )}
        >
            <span
                aria-hidden="true"
                className={cn('size-2 shrink-0 border-2', classes.mark)}
            />
            {children}
        </span>
    );
}
