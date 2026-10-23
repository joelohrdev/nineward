import { Form, Head, Link } from '@inertiajs/react';
import { PlayerFields } from '@/components/players/player-fields';
import type {
    PlayerFormOptions,
    PlayerValues,
} from '@/components/players/player-fields';
import { PageHeader } from '@/components/scorebook/page-header';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { show, update } from '@/routes/players';

type Props = PlayerFormOptions & {
    player: PlayerValues & { id: number; name: string };
};

export default function EditPlayer({ player, ...options }: Props) {
    return (
        <>
            <Head title={`Edit ${player.name}`} />

            <PageHeader title={`Edit ${player.name}`} />

            <Form
                {...update.form(player.id)}
                disableWhileProcessing
                className="grid max-w-3xl gap-8"
            >
                {({ errors, processing }) => (
                    <>
                        <PlayerFields
                            errors={errors}
                            player={player}
                            {...options}
                        />
                        <div className="flex gap-3">
                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Save player
                            </Button>
                            <Button asChild size="lg" variant="outline">
                                <Link href={show(player.id)}>Cancel</Link>
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
