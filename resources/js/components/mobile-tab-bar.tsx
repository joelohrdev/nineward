import { Link } from '@inertiajs/react';
import { Ellipsis } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn, toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

const MAX_TABS = 5;

const tabClasses = (isActive: boolean) =>
    cn(
        '-mt-0.5 flex h-16 w-full flex-col items-center justify-center gap-1 border-t-[3px]',
        isActive
            ? 'border-brand text-ink'
            : 'border-transparent text-ink-muted',
    );

/**
 * The phone navigation. Up to five tabs fit; beyond that the last slot
 * becomes a "More" menu holding the rest.
 */
export function MobileTabBar({ items }: { items: NavItem[] }) {
    const overflows = items.length > MAX_TABS;
    const visible = overflows ? items.slice(0, MAX_TABS - 1) : items;
    const more = overflows ? items.slice(MAX_TABS - 1) : [];
    const isMoreActive = more.some((item) => item.isActive);

    return (
        <nav
            aria-label="Primary"
            className="fixed inset-x-0 bottom-0 z-40 border-t-2 border-ink bg-sheet pb-[env(safe-area-inset-bottom)] md:hidden"
        >
            <ul className="grid auto-cols-fr grid-flow-col">
                {visible.map((item) => {
                    const Icon = item.icon;

                    return (
                        <li key={toUrl(item.href)}>
                            <Link
                                href={item.href}
                                aria-current={
                                    item.isActive ? 'page' : undefined
                                }
                                className={tabClasses(item.isActive ?? false)}
                            >
                                {Icon && (
                                    <Icon
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                )}
                                <span className="label-caps text-[0.6875rem]">
                                    {item.title}
                                </span>
                            </Link>
                        </li>
                    );
                })}
                {more.length > 0 && (
                    <li>
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                className={tabClasses(isMoreActive)}
                            >
                                <Ellipsis
                                    className="size-5"
                                    aria-hidden="true"
                                />
                                <span className="label-caps text-[0.6875rem]">
                                    More
                                </span>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                side="top"
                                align="end"
                                className="w-56"
                            >
                                {more.map((item) => {
                                    const Icon = item.icon;

                                    return (
                                        <DropdownMenuItem
                                            key={toUrl(item.href)}
                                            asChild
                                        >
                                            <Link
                                                href={item.href}
                                                aria-current={
                                                    item.isActive
                                                        ? 'page'
                                                        : undefined
                                                }
                                                className={cn(
                                                    'min-h-11 w-full cursor-pointer',
                                                    item.isActive &&
                                                        'font-semibold',
                                                )}
                                            >
                                                {Icon && (
                                                    <Icon aria-hidden="true" />
                                                )}
                                                {item.title}
                                            </Link>
                                        </DropdownMenuItem>
                                    );
                                })}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </li>
                )}
            </ul>
        </nav>
    );
}
