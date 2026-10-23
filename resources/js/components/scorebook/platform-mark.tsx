import type { SVGAttributes } from 'react';

/**
 * A diamond drawn as one line with home plate filled in: the scorer's mark
 * for a run that scored.
 */
export function PlatformMark(props: SVGAttributes<SVGElement>) {
    return (
        <svg viewBox="0 0 32 32" aria-hidden="true" {...props}>
            <path
                d="M16 3.5 28.5 16 16 28.5 3.5 16Z"
                fill="none"
                stroke="currentColor"
                strokeWidth="2.25"
                strokeLinejoin="miter"
            />
            <path
                d="M12.6 22.4h6.8v3.1L16 28.9l-3.4-3.4Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function Wordmark({ className = '' }: { className?: string }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 font-display text-2xl leading-none lowercase ${className}`}
        >
            <PlatformMark className="size-[1.05em]" />
            nineward
        </span>
    );
}
