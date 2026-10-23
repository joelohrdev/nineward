import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { Field } from '@/components/field';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/organization/profile';

type Profile = {
    name: string;
    slug: string;
    primaryColor: string | null;
    logoUrl: string | null;
    showPublicRosters: boolean;
    publicUrl: string;
};

const hexPattern = /#[0-9A-F]{6}/i;

export default function OrganizationProfile({ profile }: { profile: Profile }) {
    const [color, setColor] = useState(profile.primaryColor ?? '');

    return (
        <>
            <Head title="Organization profile" />

            <section className="space-y-6">
                <SectionHeading
                    title="Profile"
                    description="Your name, color and logo appear across the app and on your public pages."
                />

                <Form
                    {...update.form()}
                    options={{ preserveScroll: true }}
                    className="grid max-w-xl gap-6"
                >
                    {({ errors, processing, recentlySuccessful }) => {
                        const suggestion =
                            errors.primary_color?.match(hexPattern)?.[0];

                        return (
                            <>
                                <Field
                                    id="org-name"
                                    label="Name"
                                    error={errors.name}
                                >
                                    <Input
                                        id="org-name"
                                        name="name"
                                        defaultValue={profile.name}
                                        required
                                    />
                                </Field>

                                <Field
                                    id="org-color"
                                    label="Brand color"
                                    hint="Used for buttons, active tabs and selection marks. Leave blank for the default clay."
                                    error={errors.primary_color}
                                >
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="color"
                                            aria-label="Pick a brand color"
                                            value={color || '#B5532C'}
                                            onChange={(event) =>
                                                setColor(
                                                    event.target.value.toUpperCase(),
                                                )
                                            }
                                            className="h-11 w-14 shrink-0 cursor-pointer rounded-sm border border-rule-strong bg-sheet p-1"
                                        />
                                        <Input
                                            id="org-color"
                                            name="primary_color"
                                            value={color}
                                            onChange={(event) =>
                                                setColor(event.target.value)
                                            }
                                            placeholder="#B5532C"
                                            maxLength={7}
                                            className="font-mono uppercase"
                                        />
                                    </div>
                                </Field>
                                {suggestion && (
                                    <div>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            onClick={() =>
                                                setColor(
                                                    suggestion.toUpperCase(),
                                                )
                                            }
                                        >
                                            <span
                                                aria-hidden="true"
                                                className="size-4 rounded-sm border border-ink/30"
                                                style={{
                                                    background: suggestion,
                                                }}
                                            />
                                            Use {suggestion.toUpperCase()}
                                        </Button>
                                    </div>
                                )}

                                <Field
                                    id="org-logo"
                                    label="Logo"
                                    hint="PNG, JPG or WebP, up to 2 MB. A square image works best."
                                    error={errors.logo}
                                >
                                    <div className="flex items-center gap-4">
                                        {profile.logoUrl && (
                                            <img
                                                src={profile.logoUrl}
                                                alt="Current logo"
                                                className="size-16 shrink-0 rounded-sm border border-rule bg-sheet object-contain p-1"
                                            />
                                        )}
                                        <Input
                                            id="org-logo"
                                            name="logo"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            className="pt-2"
                                        />
                                    </div>
                                </Field>
                                {profile.logoUrl && (
                                    <div className="flex items-center gap-3">
                                        <Checkbox
                                            id="remove-logo"
                                            name="remove_logo"
                                            value="1"
                                        />
                                        <Label htmlFor="remove-logo">
                                            Remove the current logo
                                        </Label>
                                    </div>
                                )}

                                <div className="grid gap-2 border-t border-rule pt-6">
                                    <div className="flex items-center gap-3">
                                        <Checkbox
                                            id="show-public-rosters"
                                            name="show_public_rosters"
                                            value="1"
                                            defaultChecked={
                                                profile.showPublicRosters
                                            }
                                        />
                                        <Label htmlFor="show-public-rosters">
                                            Show rosters on our public page
                                        </Label>
                                    </div>
                                    <p className="text-sm text-ink-muted">
                                        Lists each team's jersey numbers, first
                                        names and last initials. Birth dates and
                                        contact details are never public. Your
                                        public page:{' '}
                                        <a
                                            href={profile.publicUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="font-semibold text-ink underline underline-offset-4"
                                        >
                                            {profile.publicUrl}
                                        </a>
                                    </p>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Save profile
                                    </Button>
                                    {recentlySuccessful && (
                                        <p className="text-sm font-medium text-grass">
                                            Saved.
                                        </p>
                                    )}
                                </div>
                            </>
                        );
                    }}
                </Form>
            </section>
        </>
    );
}
