import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { SectionHeading } from '@/components/scorebook/section-heading';
import PublicLayout from '@/layouts/public-layout';
import type { PublicOrganization } from '@/layouts/public-layout';
import { formatMoney } from '@/lib/format';
import { team as publicTeam } from '@/routes/organizations/public';
import { show as registrationForm } from '@/routes/registration';

type Props = {
    organization: PublicOrganization;
    seasonName: string | null;
    forms: {
        title: string;
        slug: string;
        seasonName: string | null;
        priceCents: number;
    }[];
    teams: { name: string; slug: string; divisionName: string }[];
    showRosters: boolean;
};

export default function PublicOrganizationPage({
    organization,
    seasonName,
    forms,
    teams,
    showRosters,
}: Props) {
    return (
        <PublicLayout organization={organization}>
            <Head title={organization.name} />

            <div className="space-y-12">
                <section className="space-y-4">
                    <SectionHeading title="Registration" />
                    {forms.length === 0 ? (
                        <p className="text-ink-muted">
                            Registration isn't open right now. Check back soon.
                        </p>
                    ) : (
                        <ul className="grid gap-3">
                            {forms.map((form) => (
                                <li key={form.slug}>
                                    <Link
                                        href={registrationForm({
                                            organization: organization.slug,
                                            form: form.slug,
                                        })}
                                        className="flex items-center justify-between gap-4 border-2 border-ink bg-sheet px-4 py-4 hover:bg-wash"
                                    >
                                        <span>
                                            <span className="block font-display text-2xl uppercase">
                                                {form.title}
                                            </span>
                                            <span className="text-sm text-ink-muted">
                                                {[
                                                    form.seasonName,
                                                    form.priceCents > 0
                                                        ? `${formatMoney(form.priceCents)} fee`
                                                        : 'Free',
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </span>
                                        </span>
                                        <ArrowRight
                                            className="size-5 shrink-0"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {seasonName !== null && teams.length > 0 && (
                    <section className="space-y-4">
                        <SectionHeading title={`${seasonName} teams`} />
                        <ul className="border-t border-rule">
                            {teams.map((team) => (
                                <li
                                    key={team.slug}
                                    className="flex items-center justify-between gap-3 border-b border-rule py-3"
                                >
                                    {showRosters ? (
                                        <Link
                                            href={publicTeam({
                                                organization: organization.slug,
                                                team: team.slug,
                                            })}
                                            className="font-semibold underline decoration-rule-strong underline-offset-4 hover:decoration-brand"
                                        >
                                            {team.name}
                                        </Link>
                                    ) : (
                                        <span className="font-semibold">
                                            {team.name}
                                        </span>
                                    )}
                                    <span className="label-caps text-ink-muted">
                                        {team.divisionName}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}
