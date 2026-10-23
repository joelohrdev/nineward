import { Link, usePage } from '@inertiajs/react';
import SupportSessionController from '@/actions/App/Http/Controllers/Platform/SupportSessionController';

/**
 * A banner that can't be dismissed while a platform admin is inside an
 * organization, so it's never forgotten that changes are being logged.
 */
export function SupportSessionBanner() {
    const { supportSession } = usePage().props;

    if (supportSession === null) {
        return null;
    }

    return (
        <div role="status" className="bg-clay text-sheet">
            <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-2 text-sm md:px-8">
                <p>
                    <span className="mr-2 label-caps">Support session</span>
                    You're inside {supportSession.organizationName}. Every
                    change you make is logged.
                </p>
                <Link
                    href={SupportSessionController.destroy()}
                    as="button"
                    className="label-caps underline underline-offset-4"
                >
                    Leave
                </Link>
            </div>
        </div>
    );
}
