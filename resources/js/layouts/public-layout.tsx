import type { CSSProperties, ReactNode } from 'react';
import { PlatformMark } from '@/components/scorebook/platform-mark';

export type PublicOrganization = {
    name: string;
    slug: string;
    color: string | null;
    logoUrl: string | null;
};

/**
 * Pages families see. The organization's brand leads; the platform only
 * appears in the footer.
 */
export default function PublicLayout({
    organization,
    children,
}: {
    organization: PublicOrganization;
    children: ReactNode;
}) {
    const brandStyle = organization.color
        ? ({ '--brand': organization.color } as CSSProperties)
        : undefined;

    return (
        <div className="flex min-h-svh flex-col bg-paper" style={brandStyle}>
            <header className="border-b-4 border-brand bg-board text-board-ink">
                <div className="mx-auto flex max-w-2xl items-center gap-4 px-4 py-5">
                    {organization.logoUrl && (
                        <img
                            src={organization.logoUrl}
                            alt=""
                            className="size-14 shrink-0 rounded-sm bg-board-ink object-contain p-1"
                        />
                    )}
                    <p className="font-display text-3xl leading-none uppercase">
                        {organization.name}
                    </p>
                </div>
            </header>

            <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-10">
                {children}
            </main>

            <footer className="border-t border-rule">
                <p className="mx-auto flex max-w-2xl items-center gap-1.5 px-4 py-6 text-sm text-ink-muted">
                    Powered by
                    <span className="inline-flex items-center gap-1 font-display text-base text-ink lowercase">
                        <PlatformMark className="size-4" />
                        nineward
                    </span>
                </p>
            </footer>
        </div>
    );
}
