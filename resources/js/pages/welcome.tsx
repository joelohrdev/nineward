import { Head, Link, usePage } from '@inertiajs/react';
import { JerseyNumber } from '@/components/scorebook/jersey-number';
import { LineupCard } from '@/components/scorebook/lineup-card';
import { Wordmark } from '@/components/scorebook/platform-mark';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const facts = [
    { value: '0%', label: 'Taken from registration fees' },
    { value: 'Flat', label: 'Monthly price, any size' },
    { value: 'All', label: 'Seasons kept on file' },
];

const steps = [
    {
        number: '01',
        title: 'Register',
        body: 'Build your own registration form. Families sign up and pay your Stripe account directly, no account required.',
    },
    {
        number: '02',
        title: 'Roster',
        body: 'Every registered player lands in one pool. Move them onto teams side by side, with jersey numbers and age divisions checked for you.',
    },
    {
        number: '03',
        title: 'Roll over',
        body: 'Start next season in a minute. Team structure carries over, kids stay on file, and last season stays in the book.',
    },
];

const samplePool = [
    { id: 1, primary: 'Mason Reyes', secondary: 'League age 10 · R/R' },
    { id: 2, primary: 'Eli Brooks', secondary: 'League age 10 · L/L' },
    { id: 3, primary: 'Owen Park', secondary: 'League age 9 · S/R' },
];

const sampleRoster = [
    {
        id: 4,
        primary: 'Caleb Ortiz',
        secondary: 'C · Captain',
        leading: <JerseyNumber number={7} />,
    },
    {
        id: 5,
        primary: 'Jack Daley',
        secondary: 'SS',
        leading: <JerseyNumber number={2} />,
    },
    {
        id: 6,
        primary: 'Noah Kim',
        secondary: 'P / 1B',
        leading: <JerseyNumber number={23} />,
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Rosters and registration for travel baseball" />

            <div className="min-h-svh bg-paper text-ink">
                <header className="bg-board text-board-ink">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 md:px-8">
                        <Wordmark className="text-2xl sm:text-3xl" />
                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild variant="brand">
                                    <Link href={dashboard()}>
                                        Open the book
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="px-2 py-2 label-caps whitespace-nowrap text-board-ink/85 hover:text-board-ink sm:px-3"
                                    >
                                        Log in
                                    </Link>
                                    <Button asChild variant="brand">
                                        <Link href={register()}>
                                            Start free trial
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <section className="border-b-2 border-ink ruled">
                        <div className="mx-auto grid max-w-6xl gap-12 px-4 py-16 md:px-8 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-center lg:py-24">
                            <div>
                                <h1 className="font-display text-[clamp(3.25rem,9vw,7rem)] leading-[0.86] uppercase">
                                    The scorebook
                                    <br />
                                    for your whole
                                    <br />
                                    organization.
                                </h1>
                                <p className="mt-6 max-w-lg text-lg text-ink-muted">
                                    Registration, the player pool and team
                                    rosters for travel baseball, in one place
                                    and kept season after season.
                                </p>
                                <div className="mt-8 flex flex-wrap gap-3">
                                    <Button asChild size="lg">
                                        <Link href={register()}>
                                            Start free trial
                                        </Link>
                                    </Button>
                                    <Button asChild size="lg" variant="outline">
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                </div>
                            </div>

                            <div
                                aria-label="Example of the roster builder"
                                className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2"
                            >
                                <LineupCard
                                    title="Unassigned · 10U"
                                    entries={samplePool}
                                    selectedIds={[1]}
                                    onToggle={() => {}}
                                    empty=""
                                />
                                <LineupCard
                                    title="10U Red"
                                    entries={sampleRoster}
                                    selectedIds={[]}
                                    onToggle={() => {}}
                                    empty=""
                                />
                            </div>
                        </div>
                    </section>

                    <section className="bg-board text-board-ink">
                        <dl className="mx-auto grid max-w-6xl grid-cols-3">
                            {facts.map((fact) => (
                                <div
                                    key={fact.label}
                                    className="flex flex-col-reverse justify-end gap-2 border-r border-board-rule px-4 py-6 last:border-r-0 sm:py-8 md:px-8"
                                >
                                    <dt className="label-caps text-board-ink/70">
                                        {fact.label}
                                    </dt>
                                    <dd className="font-display text-4xl leading-none text-bulb uppercase sm:text-6xl">
                                        {fact.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>

                    <section className="mx-auto max-w-6xl px-4 py-16 md:px-8 lg:py-24">
                        <ol className="grid gap-10 md:grid-cols-3">
                            {steps.map((step) => (
                                <li
                                    key={step.number}
                                    className="border-t-2 border-ink pt-4"
                                >
                                    <span className="font-display text-xl text-clay">
                                        {step.number}
                                    </span>
                                    <h2 className="mt-1 font-display text-4xl uppercase">
                                        {step.title}
                                    </h2>
                                    <p className="mt-3 text-ink-muted">
                                        {step.body}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>
                </main>

                <footer className="border-t border-rule">
                    <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-8 text-sm text-ink-muted md:px-8">
                        <Wordmark className="text-xl text-ink" />
                        <p>Built for travel baseball organizations.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
