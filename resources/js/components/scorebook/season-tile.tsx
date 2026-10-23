import { ChevronDown } from 'lucide-react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Props = ComponentProps<'button'> & {
    name: string;
    isFinal?: boolean;
};

/**
 * The current season, shown as a scoreboard panel in the top bar.
 */
export function SeasonTile({
    name,
    isFinal = false,
    className,
    ...props
}: Props) {
    return (
        <button
            type="button"
            className={cn(
                'inline-flex h-10 shrink-0 items-center gap-2 rounded-sm border border-board-rule bg-black/25 px-3 whitespace-nowrap text-board-ink transition-colors hover:bg-black/40',
                className,
            )}
            {...props}
        >
            <span className="font-display text-base leading-none text-bulb uppercase sm:text-lg">
                {name}
            </span>
            {isFinal && (
                <span className="hidden rounded-sm bg-board-ink/15 px-1 py-0.5 label-caps text-[0.625rem] text-board-ink sm:inline">
                    Final
                </span>
            )}
            <ChevronDown className="size-4 opacity-70" aria-hidden="true" />
            <span className="sr-only">Switch season</span>
        </button>
    );
}
