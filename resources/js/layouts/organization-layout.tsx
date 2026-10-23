import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { SectionedPage } from '@/components/scorebook/sectioned-page';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { index as divisions } from '@/routes/organization/divisions';
import { index as locations } from '@/routes/organization/locations';
import { index as members } from '@/routes/organization/members';
import { show as payments } from '@/routes/organization/payments';
import { edit as profile } from '@/routes/organization/profile';
import { index as seasons } from '@/routes/organization/seasons';
import type { NavItem } from '@/types';

export default function OrganizationLayout({ children }: PropsWithChildren) {
    const { currentOrganization } = usePage().props;
    const { currentUrl } = useCurrentUrl();
    const sections: NavItem[] = [
        {
            title: 'Seasons',
            href: seasons(),
            isActive: currentUrl.startsWith('/organization/season'),
        },
        { title: 'Divisions', href: divisions() },
        { title: 'Locations', href: locations() },
        { title: 'Members', href: members() },
        { title: 'Payments', href: payments() },
        { title: 'Profile', href: profile() },
    ];

    return (
        <SectionedPage
            title={currentOrganization?.name ?? 'Organization'}
            context="Seasons, divisions, fields, people, payments and branding"
            label="Organization sections"
            sections={sections}
            wide
        >
            {children}
        </SectionedPage>
    );
}
