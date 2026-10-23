import type { ReactNode } from 'react';

type Props = {
    title: string;
    context?: ReactNode;
    actions?: ReactNode;
};

export function PageHeader({ title, context, actions }: Props) {
    return (
        <header className="mb-8 border-b-2 border-ink pt-8 pb-4 md:pt-10">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="min-w-0">
                    <h1 className="font-display text-4xl leading-[0.95] uppercase md:text-5xl">
                        {title}
                    </h1>
                    {context && (
                        <p className="mt-2 text-sm text-ink-muted">{context}</p>
                    )}
                </div>
                {actions && (
                    <div className="flex shrink-0 items-center gap-2">
                        {actions}
                    </div>
                )}
            </div>
        </header>
    );
}
