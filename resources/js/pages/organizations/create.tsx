import { Form, Head } from '@inertiajs/react';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function CreateOrganization({
    trialDays,
}: {
    trialDays: number;
}) {
    return (
        <>
            <Head title="Create your organization" />

            <Form
                {...OrganizationController.store.form()}
                disableWhileProcessing
                className="grid gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Organization name</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autoFocus
                                autoComplete="organization"
                                placeholder="Cary Trojans Baseball"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <p className="border-l-4 border-bulb bg-sheet px-3 py-2 text-sm">
                            Your {trialDays}-day free trial starts today. No
                            card needed until you subscribe.
                        </p>

                        <Button type="submit" size="lg" className="w-full">
                            {processing && <Spinner />}
                            Create organization
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

CreateOrganization.layout = {
    title: 'Set up your organization',
    description:
        'Name the club or program you run. You can invite admins and coaches next.',
};
