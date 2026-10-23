export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_platform_admin: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type OrganizationRole = 'owner' | 'admin' | 'coach';

export type CurrentOrganization = {
    id: number;
    name: string;
    slug: string;
    primaryColor: string | null;
    brandTextColor: string | null;
    logoUrl: string | null;
    role: OrganizationRole | null;
    trialEndsAt: string | null;
    isOnTrial: boolean;
};

export type SeasonSummary = {
    id: number;
    name: string;
    isActive: boolean;
    hasEnded: boolean;
};

export type SupportSession = {
    organizationName: string;
};

export type OrganizationSummary = {
    id: number;
    name: string;
};

export type Auth = {
    user: User;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
