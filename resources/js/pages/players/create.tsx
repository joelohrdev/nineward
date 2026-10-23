import { Form, Head } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import { GuardianFields } from '@/components/players/guardian-fields';
import { PlayerFields } from '@/components/players/player-fields';
import type { PlayerFormOptions } from '@/components/players/player-fields';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/scorebook/page-header';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/players';

const MAX_GUARDIANS = 4;

export default function CreatePlayer(options: PlayerFormOptions) {
    const [guardianKeys, setGuardianKeys] = useState([0]);
    const [nextKey, setNextKey] = useState(1);

    const addGuardian = () => {
        setGuardianKeys((keys) => [...keys, nextKey]);
        setNextKey((key) => key + 1);
    };

    return (
        <>
            <Head title="Add a player" />

            <PageHeader
                title="Add a player"
                context="Add them once. They stay in your master list every season."
            />

            <Form
                {...store.form()}
                disableWhileProcessing
                className="grid max-w-3xl gap-12"
            >
                {({ errors, processing }) => (
                    <>
                        <section className="space-y-5">
                            <SectionHeading title="Player" />
                            <PlayerFields errors={errors} {...options} />
                        </section>

                        <section className="space-y-5">
                            <SectionHeading
                                title="Parents and guardians"
                                description="Someone already in your list with the same email is reused, so siblings share one guardian."
                            />
                            <InputError message={errors.guardians} />
                            {guardianKeys.map((key, index) => (
                                <fieldset
                                    key={key}
                                    className="space-y-4 border-l-4 border-rule pl-4"
                                >
                                    <legend className="flex w-full items-center justify-between">
                                        <span className="label-caps text-ink-muted">
                                            Guardian {index + 1}
                                        </span>
                                        {guardianKeys.length > 1 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setGuardianKeys((keys) =>
                                                        keys.filter(
                                                            (k) => k !== key,
                                                        ),
                                                    )
                                                }
                                            >
                                                <X />
                                                Remove
                                            </Button>
                                        )}
                                    </legend>
                                    <GuardianFields
                                        errors={errors}
                                        idPrefix={`guardian-${key}`}
                                        namePrefix={`guardians[${index}]`}
                                        errorPrefix={`guardians.${index}.`}
                                    />
                                </fieldset>
                            ))}
                            {guardianKeys.length < MAX_GUARDIANS && (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={addGuardian}
                                >
                                    <Plus />
                                    Add another guardian
                                </Button>
                            )}
                        </section>

                        <div>
                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Add player
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
