import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { login, logout, register } from '@/routes';

type Props = {
    invitation: {
        organizationName: string;
        organizationColor: string | null;
        inviterName: string;
        email: string;
        role: string;
        isPending: boolean;
    };
    signedInEmail: string | null;
    isForSignedInUser: boolean;
    acceptUrl: string;
};

export default function ShowInvitation({
    invitation,
    signedInEmail,
    isForSignedInUser,
    acceptUrl,
}: Props) {
    setLayoutProps({
        title: `Join ${invitation.organizationName}`,
        description: `${invitation.inviterName} invited ${invitation.email} to join as a ${invitation.role.toLowerCase()}.`,
    });

    if (!invitation.isPending) {
        return (
            <>
                <Head title="Invitation" />
                <p className="border-l-4 border-danger bg-sheet px-3 py-2">
                    This invitation has expired or was already used. Ask{' '}
                    {invitation.inviterName} to send a new one.
                </p>
            </>
        );
    }

    return (
        <>
            <Head title={`Join ${invitation.organizationName}`} />

            {invitation.organizationColor && (
                <div
                    aria-hidden="true"
                    className="mb-6 h-2 rounded-sm"
                    style={{ background: invitation.organizationColor }}
                />
            )}

            {signedInEmail === null && (
                <div className="grid gap-3">
                    <p className="text-ink-muted">
                        Sign in or create an account with{' '}
                        <strong className="text-ink">{invitation.email}</strong>{' '}
                        to accept.
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
                        You're signed in as {signedInEmail}, but this invitation
                        is for {invitation.email}.
                    </p>
                    <Button asChild variant="outline">
                        <Link href={logout()} as="button">
                            Log out and switch accounts
                        </Link>
                    </Button>
                </div>
            )}

            {isForSignedInUser && (
                <Form action={acceptUrl} method="post">
                    {({ processing, errors }) => (
                        <div className="grid gap-3">
                            <InputError message={errors.invitation} />
                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Accept and join {invitation.organizationName}
                            </Button>
                        </div>
                    )}
                </Form>
            )}
        </>
    );
}
