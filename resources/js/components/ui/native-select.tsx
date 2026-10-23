import { ChevronDown } from 'lucide-react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * A styled native select. Native controls submit with plain forms and work
 * well with phone pickers.
 */
function NativeSelect({ className, children, ...props }: ComponentProps<'select'>) {
    return (
        <span className="relative block">
            <select
                className={cn(
                    'h-11 w-full appearance-none rounded-sm border border-b-2 border-rule border-b-rule-strong bg-sheet pr-9 pl-3 text-base text-ink transition-colors outline-none focus-visible:border-b-clay focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay disabled:opacity-50 aria-invalid:border-b-danger md:h-10 md:text-[0.9375rem]',
                    className,
                )}
                {...props}
            >
                {children}
            </select>
            <ChevronDown
                aria-hidden="true"
                className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-ink-muted"
            />
        </span>
    );
}

export { NativeSelect };
