import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PageHeader } from '@/components/scorebook/page-header';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

type Props = {
    title: string;
    context: string;
    label: string;
    sections: NavItem[];
    children: ReactNode;
    wide?: boolean;
};

/**
 * A page header plus a numbered section index: tabs on phones, a sticky
 * ruled index on desktop.
 */
export function SectionedPage({
    title,
    context,
    label,
    sections,
    children,
    wide = false,
}: Props) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <>
            <PageHeader title={title} context={context} />

            <div className="grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[12rem_minmax(0,1fr)] lg:gap-12">
                <nav aria-label={label} className="min-w-0">
                    <ol className="flex [scrollbar-width:none] gap-1 overflow-x-auto border-b border-rule lg:sticky lg:top-6 lg:flex-col lg:gap-0 lg:border-b-0 lg:border-l">
                        {sections.map((section, index) => {
                            const isActive =
                                section.isActive ??
                                isCurrentOrParentUrl(section.href);

                            return (
                                <li key={toUrl(section.href)}>
                                    <Link
                                        href={section.href}
                                        prefetch
                                        aria-current={
                                            isActive ? 'page' : undefined
                                        }
                                        className={cn(
                                            'flex h-11 items-center gap-3 border-b-[3px] px-3 whitespace-nowrap transition-colors lg:-ml-px lg:border-b-0 lg:border-l-[3px]',
                                            isActive
                                                ? 'border-brand text-ink'
                                                : 'border-transparent text-ink-muted hover:text-ink',
                                        )}
                                    >
                                        <span className="font-display text-base text-ink-muted tabular-nums">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <span className="label-caps">
                                            {section.title}
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ol>
                </nav>

                <div
                    className={cn('space-y-14', wide ? 'min-w-0' : 'max-w-2xl')}
                >
                    {children}
                </div>
            </div>
        </>
    );
}
