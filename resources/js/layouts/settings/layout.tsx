import type { PropsWithChildren } from 'react';
import { SectionedPage } from '@/components/scorebook/sectioned-page';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sections: NavItem[] = [
    { title: 'Profile', href: edit() },
    { title: 'Security', href: editSecurity() },
    { title: 'Appearance', href: editAppearance() },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    return (
        <SectionedPage
            title="Settings"
            context="Your profile, password and display preferences"
            label="Settings sections"
            sections={sections}
        >
            {children}
        </SectionedPage>
    );
}
