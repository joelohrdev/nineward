import { Field } from '@/components/field';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';

export type Option = { value: string; label: string };

export type PlayerFormOptions = {
    handedness: Option[];
    throwingArms: Option[];
    positions: Option[];
};

export type PlayerValues = {
    first_name: string;
    last_name: string;
    date_of_birth: string;
    graduation_year: number | null;
    bats: string | null;
    throws: string | null;
    primary_position: string | null;
    notes: string | null;
};

type Props = PlayerFormOptions & {
    errors: Record<string, string>;
    player?: PlayerValues;
};

function OptionalSelect({
    id,
    name,
    options,
    value,
}: {
    id: string;
    name: string;
    options: Option[];
    value: string | null | undefined;
}) {
    return (
        <NativeSelect id={id} name={name} defaultValue={value ?? ''}>
            <option value="">Not set</option>
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </NativeSelect>
    );
}

export function PlayerFields({
    errors,
    player,
    handedness,
    throwingArms,
    positions,
}: Props) {
    return (
        <div className="grid gap-5">
            <div className="grid gap-5 sm:grid-cols-2">
                <Field
                    id="first_name"
                    label="First name"
                    error={errors.first_name}
                >
                    <Input
                        id="first_name"
                        name="first_name"
                        defaultValue={player?.first_name}
                        autoComplete="off"
                        required
                    />
                </Field>
                <Field
                    id="last_name"
                    label="Last name"
                    error={errors.last_name}
                >
                    <Input
                        id="last_name"
                        name="last_name"
                        defaultValue={player?.last_name}
                        autoComplete="off"
                        required
                    />
                </Field>
            </div>
            <div className="grid gap-5 sm:grid-cols-2">
                <Field
                    id="date_of_birth"
                    label="Date of birth"
                    error={errors.date_of_birth}
                >
                    <Input
                        id="date_of_birth"
                        name="date_of_birth"
                        type="date"
                        defaultValue={player?.date_of_birth}
                        required
                    />
                </Field>
                <Field
                    id="graduation_year"
                    label="High school graduation year"
                    error={errors.graduation_year}
                >
                    <Input
                        id="graduation_year"
                        name="graduation_year"
                        type="number"
                        inputMode="numeric"
                        min={2000}
                        max={2100}
                        defaultValue={player?.graduation_year ?? undefined}
                    />
                </Field>
            </div>
            <div className="grid gap-5 sm:grid-cols-3">
                <Field id="bats" label="Bats" error={errors.bats}>
                    <OptionalSelect
                        id="bats"
                        name="bats"
                        options={handedness}
                        value={player?.bats}
                    />
                </Field>
                <Field id="throws" label="Throws" error={errors.throws}>
                    <OptionalSelect
                        id="throws"
                        name="throws"
                        options={throwingArms}
                        value={player?.throws}
                    />
                </Field>
                <Field
                    id="primary_position"
                    label="Primary position"
                    error={errors.primary_position}
                >
                    <OptionalSelect
                        id="primary_position"
                        name="primary_position"
                        options={positions}
                        value={player?.primary_position}
                    />
                </Field>
            </div>
            <Field
                id="notes"
                label="Notes"
                hint="Only your admins see these."
                error={errors.notes}
            >
                <textarea
                    id="notes"
                    name="notes"
                    rows={3}
                    defaultValue={player?.notes ?? undefined}
                    className="w-full rounded-sm border border-b-2 border-rule border-b-rule-strong bg-sheet px-3 py-2 text-base text-ink outline-none focus-visible:border-b-clay focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay md:text-[0.9375rem]"
                />
            </Field>
        </div>
    );
}
