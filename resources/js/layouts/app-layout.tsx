import { Link, usePage } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';
import { MobileTabBar } from '@/components/mobile-tab-bar';
import { OrganizationSwitcher } from '@/components/organization-switcher';
import { SeasonSwitcher } from '@/components/season-switcher';
import { SupportSessionBanner } from '@/components/support-session-banner';
import { TrialChip } from '@/components/trial-chip';
import { UserMenu } from '@/components/user-menu';
import { useAppNavigation } from '@/hooks/use-app-navigation';
import { cn, toUrl } from '@/lib/utils';

export default function AppLayout({ children }: { children: ReactNode }) {
    const navigation = useAppNavigation();
    const { currentOrganization } = usePage().props;
    const brandStyle =
        currentOrganization?.primaryColor && currentOrganization.brandTextColor
            ? ({
                  '--brand': currentOrganization.primaryColor,
                  '--brand-ink': currentOrganization.brandTextColor,
              } as CSSProperties)
            : undefined;

    return (
        <div
            className="flex min-h-svh flex-col bg-paper pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-0"
            style={brandStyle}
        >
            <SupportSessionBanner />
            <header className="bg-board text-board-ink">
                <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 md:px-8">
                    <OrganizationSwitcher />
                    <div className="ml-auto flex items-center gap-2">
                        <SeasonSwitcher />
                        <TrialChip />
                        <UserMenu />
                    </div>
                </div>
            </header>

            <nav
                aria-label="Primary"
                className="hidden border-b border-rule bg-paper md:block"
            >
                <ul className="mx-auto flex max-w-6xl gap-1 px-8">
                    {navigation.map((item) => {
                        const isActive = item.isActive ?? false;

                        return (
                            <li key={toUrl(item.href)}>
                                <Link
                                    href={item.href}
                                    prefetch
                                    aria-current={isActive ? 'page' : undefined}
                                    className={cn(
                                        'flex h-12 items-center border-b-[3px] px-3 label-caps transition-colors',
                                        isActive
                                            ? 'border-brand text-ink'
                                            : 'border-transparent text-ink-muted hover:text-ink',
                                    )}
                                >
                                    {item.title}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 pb-16 md:px-8">
                {children}
            </main>

            <MobileTabBar items={navigation} />
        </div>
    );
}
