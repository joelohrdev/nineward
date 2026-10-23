import type {
    Auth,
    CurrentOrganization,
    OrganizationSummary,
    SeasonSummary,
    SupportSession,
} from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            currentOrganization: CurrentOrganization | null;
            organizations: OrganizationSummary[];
            supportSession: SupportSession | null;
            hasClaimedPlayers: boolean;
            seasons: SeasonSummary[];
            selectedSeasonId: number | null;
            [key: string]: unknown;
        };
    }
}
