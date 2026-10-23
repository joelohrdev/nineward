import { usePage } from '@inertiajs/react';
import {
    Building2,
    ClipboardList,
    CreditCard,
    House,
    Shield,
    UserRound,
    Users,
    ShieldCheck,
    SlidersHorizontal,
} from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { dashboard } from '@/routes';
import { show as billing } from '@/routes/billing';
import { index as forms } from '@/routes/forms';
import { index as myPlayers } from '@/routes/my-players';
import { index as players } from '@/routes/players';
import { index as teams } from '@/routes/seasons/teams';
import { index as seasons } from '@/routes/organization/seasons';
import { index as platformOrganizations } from '@/routes/platform/organizations';
import { edit as editProfile } from '@/routes/profile';
import type { NavItem } from '@/types';

/**
 * Primary navigation tabs. Later phases add Teams, Players and
 * Registrations here.
 */
export function useAppNavigation(): NavItem[] {
    const {
        auth,
        currentOrganization,
        hasClaimedPlayers,
        seasons: organizationSeasons,
        selectedSeasonId,
    } = usePage().props;
    const shownSeason =
        organizationSeasons.find((season) => season.id === selectedSeasonId) ??
        organizationSeasons.find((season) => season.isActive);
    const { currentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const items: NavItem[] = [];

    if (currentOrganization !== null) {
        items.push({
            title: 'Home',
            href: dashboard(),
            icon: House,
            isActive: isCurrentOrParentUrl(dashboard()),
        });
    }

    if (currentOrganization !== null && shownSeason !== undefined) {
        items.push({
            title: 'Teams',
            href: teams(shownSeason.id),
            icon: Shield,
            isActive: currentUrl.startsWith('/seasons/'),
        });
    }

    if (
        currentOrganization?.role === 'owner' ||
        currentOrganization?.role === 'admin'
    ) {
        items.push({
            title: 'Players',
            href: players(),
            icon: Users,
            isActive: currentUrl.startsWith('/players'),
        });
        items.push({
            title: 'Registration',
            href: forms(),
            icon: ClipboardList,
            isActive:
                currentUrl.startsWith('/forms') ||
                currentUrl.startsWith('/submissions'),
        });
        items.push({
            title: 'Organization',
            href: seasons(),
            icon: Building2,
            isActive: currentUrl.startsWith('/organization/'),
        });
        items.push({
            title: 'Billing',
            href: billing(),
            icon: CreditCard,
            isActive: isCurrentOrParentUrl(billing()),
        });
    }

    if (hasClaimedPlayers) {
        items.push({
            title: 'My players',
            href: myPlayers(),
            icon: UserRound,
            isActive: isCurrentOrParentUrl(myPlayers()),
        });
    }

    items.push({
        title: 'Settings',
        href: editProfile(),
        icon: SlidersHorizontal,
        isActive: currentUrl.startsWith('/settings'),
    });

    if (auth.user.is_platform_admin) {
        items.push({
            title: 'Platform',
            href: platformOrganizations(),
            icon: ShieldCheck,
            isActive: currentUrl.startsWith('/platform'),
        });
    }

    return items;
}
