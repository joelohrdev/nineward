import type { StatusTone } from '@/components/scorebook/status-mark';

export type PlatformStatus = 'subscribed' | 'trial' | 'lapsed';

export const platformStatusTone: Record<PlatformStatus, StatusTone> = {
    subscribed: 'done',
    trial: 'open',
    lapsed: 'failed',
};

export const platformStatusLabel: Record<PlatformStatus, string> = {
    subscribed: 'Subscribed',
    trial: 'Trial',
    lapsed: 'Lapsed',
};
