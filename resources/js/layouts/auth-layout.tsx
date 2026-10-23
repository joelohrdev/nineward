import { Link } from '@inertiajs/react';
import { Wordmark } from '@/components/scorebook/platform-mark';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthLayout({
    children,
    title = '',
    description = '',
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh grid-rows-[auto_1fr] bg-paper lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] lg:grid-rows-1">
            <aside className="relative flex flex-col justify-between overflow-hidden bg-board px-6 py-6 text-board-ink lg:px-12 lg:py-10">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 bg-[repeating-linear-gradient(to_bottom,transparent_0,transparent_47px,var(--board-rule)_47px,var(--board-rule)_48px)] opacity-60"
                />
                <Link href={home()} className="relative w-fit">
                    <Wordmark className="text-3xl" />
                </Link>

                <p className="relative mt-16 hidden font-display text-[clamp(3.5rem,6vw,5.5rem)] leading-[0.88] uppercase lg:block">
                    Every roster.
                    <br />
                    Every season.
                    <br />
                    <span className="text-bulb">One book.</span>
                </p>

                <p className="relative mt-10 hidden max-w-sm text-sm text-board-ink/70 lg:block">
                    Registration, players and rosters for travel baseball
                    organizations.
                </p>
            </aside>

            <section className="flex items-start justify-center px-4 py-10 lg:items-center lg:px-12">
                <div className="w-full max-w-md">
                    <header className="mb-8 border-b-2 border-ink pb-4">
                        <h1 className="font-display text-4xl leading-none uppercase">
                            {title}
                        </h1>
                        {description && (
                            <p className="mt-2 text-ink-muted">{description}</p>
                        )}
                    </header>
                    {children}
                </div>
            </section>
        </div>
    );
}
