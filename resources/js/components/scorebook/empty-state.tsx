import type { ReactNode } from 'react';

export function EmptyState({
    message,
    action,
}: {
    message: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center gap-4 border-y border-rule px-4 py-12 text-center">
            <svg
                viewBox="0 0 64 64"
                aria-hidden="true"
                className="size-14 text-rule-strong"
            >
                <path
                    d="M32 8 56 32 32 56 8 32Z"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.5"
                    strokeDasharray="3 3"
                />
                <rect
                    x="29"
                    y="29"
                    width="6"
                    height="6"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.5"
                />
            </svg>
            <p className="max-w-sm text-ink-muted">{message}</p>
            {action}
        </div>
    );
}
