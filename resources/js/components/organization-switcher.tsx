import { Link, router, usePage } from '@inertiajs/react';
import { Check, ChevronDown } from 'lucide-react';
import { Wordmark } from '@/components/scorebook/platform-mark';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import CurrentOrganizationController from '@/actions/App/Http/Controllers/CurrentOrganizationController';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { dashboard } from '@/routes';

/**
 * The organization's name in the top bar. Members of more than one
 * organization can switch between them from here.
 */
export function OrganizationSwitcher() {
    const { currentOrganization, organizations } = usePage().props;
    const { currentUrl } = useCurrentUrl();

    if (currentUrl.startsWith('/platform')) {
        return (
            <span className="flex min-w-0 items-center gap-3">
                <Wordmark />
                <span className="rounded-sm bg-bulb px-1.5 py-1 label-caps text-board">
                    Platform
                </span>
            </span>
        );
    }

    if (currentOrganization === null) {
        return (
            <Link href={dashboard()} className="min-w-0 truncate" prefetch>
                <Wordmark />
            </Link>
        );
    }

    const name = (
        <span className="flex min-w-0 items-center gap-3">
            {currentOrganization.logoUrl && (
                <img
                    src={currentOrganization.logoUrl}
                    alt=""
                    className="size-10 shrink-0 rounded-sm bg-board-ink object-contain p-0.5"
                />
            )}
            <span className="truncate font-display text-2xl leading-none uppercase">
                {currentOrganization.name}
            </span>
        </span>
    );

    if (organizations.length < 2) {
        return (
            <Link href={dashboard()} className="flex min-w-0" prefetch>
                {name}
            </Link>
        );
    }

    const switchTo = (organizationId: number) =>
        router.put(CurrentOrganizationController.update.url(), {
            organization_id: organizationId,
        });

    return (
        <DropdownMenu>
            <DropdownMenuTrigger className="flex min-w-0 items-center gap-2 rounded-sm py-1 pr-1 text-left">
                {name}
                <ChevronDown
                    className="size-4 shrink-0 opacity-70"
                    aria-hidden="true"
                />
                <span className="sr-only">Switch organization</span>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-64">
                <DropdownMenuLabel className="label-caps text-ink-muted">
                    Your organizations
                </DropdownMenuLabel>
                {organizations.map((organization) => {
                    const isCurrent =
                        organization.id === currentOrganization.id;

                    return (
                        <DropdownMenuItem
                            key={organization.id}
                            disabled={isCurrent}
                            onSelect={() => switchTo(organization.id)}
                        >
                            <span className="flex-1 truncate">
                                {organization.name}
                            </span>
                            {isCurrent && (
                                <Check aria-label="Current organization" />
                            )}
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
