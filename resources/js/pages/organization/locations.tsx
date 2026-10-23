import { Head } from '@inertiajs/react';
import { ExternalLink, Plus } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, store, update } from '@/routes/organization/locations';

type Location = {
    id: number;
    name: string;
    address: string;
    mapsUrl: string | null;
};

function LocationFields({
    errors,
    location,
}: {
    errors: Record<string, string>;
    location?: Location;
}) {
    const prefix = location ? `location-${location.id}` : 'new-location';

    return (
        <>
            <Field id={`${prefix}-name`} label="Name" error={errors.name}>
                <Input
                    id={`${prefix}-name`}
                    name="name"
                    defaultValue={location?.name}
                    placeholder="Main Park Field 1"
                    required
                />
            </Field>
            <Field
                id={`${prefix}-address`}
                label="Address"
                error={errors.address}
            >
                <Input
                    id={`${prefix}-address`}
                    name="address"
                    defaultValue={location?.address}
                    autoComplete="street-address"
                    required
                />
            </Field>
            <Field
                id={`${prefix}-maps`}
                label="Map link"
                hint="Optional. Paste a Google or Apple Maps link."
                error={errors.maps_url}
            >
                <Input
                    id={`${prefix}-maps`}
                    name="maps_url"
                    type="url"
                    defaultValue={location?.mapsUrl ?? undefined}
                    placeholder="https://maps.google.com/…"
                />
            </Field>
        </>
    );
}

export default function Locations({ locations }: { locations: Location[] }) {
    return (
        <>
            <Head title="Locations" />

            <section className="space-y-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div className="min-w-0 flex-1">
                        <SectionHeading
                            title="Locations"
                            description="Fields and facilities you play and practice at."
                        />
                    </div>
                    <FormDialog
                        title="New location"
                        trigger={
                            <Button>
                                <Plus />
                                New location
                            </Button>
                        }
                        form={store.form()}
                        submitLabel="Add location"
                    >
                        {(errors) => <LocationFields errors={errors} />}
                    </FormDialog>
                </div>

                {locations.length === 0 ? (
                    <EmptyState message="No locations yet." />
                ) : (
                    <DataTable
                        caption="Locations"
                        rows={locations}
                        rowKey={(location) => location.id}
                        columns={[
                            {
                                key: 'name',
                                header: 'Location',
                                primary: true,
                                cell: (location) => location.name,
                            },
                            {
                                key: 'address',
                                header: 'Address',
                                cell: (location) =>
                                    location.mapsUrl ? (
                                        <a
                                            href={location.mapsUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1 underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                                        >
                                            {location.address}
                                            <ExternalLink
                                                className="size-3.5"
                                                aria-label="Opens a map in a new tab"
                                            />
                                        </a>
                                    ) : (
                                        location.address
                                    ),
                            },
                            {
                                key: 'actions',
                                header: 'Actions',
                                className: 'md:text-right',
                                cell: (location) => (
                                    <span className="inline-flex gap-1 md:justify-end">
                                        <FormDialog
                                            title={`Edit ${location.name}`}
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                >
                                                    Edit
                                                </Button>
                                            }
                                            form={update.form(location.id)}
                                            submitLabel="Save location"
                                        >
                                            {(errors) => (
                                                <LocationFields
                                                    errors={errors}
                                                    location={location}
                                                />
                                            )}
                                        </FormDialog>
                                        <ConfirmDialog
                                            title={`Delete ${location.name}?`}
                                            description="This removes the location from your list."
                                            confirmLabel="Delete location"
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                >
                                                    Delete
                                                </Button>
                                            }
                                            form={destroy.form(location.id)}
                                        />
                                    </span>
                                ),
                            },
                        ]}
                    />
                )}
            </section>
        </>
    );
}
