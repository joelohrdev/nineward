import { Link, usePage } from '@inertiajs/react';
import { daysUntil } from '@/lib/format';
import { show as billing } from '@/routes/billing';

/**
 * Days left on the free trial, shown in the top bar and linking to billing.
 */
export function TrialChip() {
    const { currentOrganization } = usePage().props;

    if (!currentOrganization?.isOnTrial) {
        return null;
    }

    const days = daysUntil(currentOrganization.trialEndsAt);

    return (
        <Link
            href={billing()}
            className="hidden h-10 items-center gap-2 rounded-sm border border-board-rule px-3 sm:inline-flex"
        >
            <span className="label-caps text-board-ink/70">Trial</span>
            <span className="font-display text-lg leading-none text-bulb tabular-nums">
                {days}
            </span>
            <span className="label-caps text-board-ink/70">
                {days === 1 ? 'day left' : 'days left'}
            </span>
        </Link>
    );
}
