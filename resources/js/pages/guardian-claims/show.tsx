import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { login, logout, register } from '@/routes';
import { index as myPlayers } from '@/routes/my-players';

type Props = {
    claim: {
        organizationName: string;
        organizationColor: string | null;
        guardianName: string;
        email: string;
        isClaimed: boolean;
        isClaimedBySignedInUser: boolean;
    };
    signedInEmail: string | null;
    isForSignedInUser: boolean;
    claimUrl: string;
};

export default function ShowGuardianClaim({
    claim,
    signedInEmail,
    isForSignedInUser,
    claimUrl,
}: Props) {
    setLayoutProps({
        title: `See your players`,
        description: `${claim.organizationName} invited ${claim.email} to see their players' details.`,
    });

    if (claim.isClaimedBySignedInUser) {
        return (
            <>
                <Head title="Your players" />
                <Button asChild size="lg" className="w-full">
                    <Link href={myPlayers()}>Go to your players</Link>
                </Button>
            </>
        );
    }

    if (claim.isClaimed) {
        return (
            <>
                <Head title="Already claimed" />
                <p className="border-l-4 border-warn bg-sheet px-3 py-2">
                    This guardian is already linked to an account. Log in with
                    that account, or ask {claim.organizationName} for help.
                </p>
            </>
        );
    }

    return (
        <>
            <Head title="See your players" />

            {claim.organizationColor && (
                <div
                    aria-hidden="true"
                    className="mb-6 h-2 rounded-sm"
                    style={{ background: claim.organizationColor }}
                />
            )}

            {signedInEmail === null && (
                <div className="grid gap-3">
                    <p className="text-ink-muted">
                        Hi {claim.guardianName}. Create an account or log in
                        with <strong className="text-ink">{claim.email}</strong>
                        .
                    </p>
                    <Button asChild size="lg">
                        <Link href={register()}>Create account</Link>
                    </Button>
                    <Button asChild size="lg" variant="outline">
                        <Link href={login()}>Log in</Link>
                    </Button>
                </div>
            )}

            {signedInEmail !== null && !isForSignedInUser && (
                <div className="grid gap-4">
                    <p className="border-l-4 border-warn bg-sheet px-3 py-2">
                        You're signed in as {signedInEmail}, but this link is
                        for {claim.email}.
                    </p>
                    <Button asChild variant="outline">
                        <Link href={logout()} as="button">
                            Log out and switch accounts
                        </Link>
                    </Button>
                </div>
            )}

            {isForSignedInUser && (
                <Form action={claimUrl} method="post">
                    {({ processing, errors }) => (
                        <div className="grid gap-3">
                            <InputError message={errors.claim} />
                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Link to my account
                            </Button>
                        </div>
                    )}
                </Form>
            )}
        </>
    );
}
