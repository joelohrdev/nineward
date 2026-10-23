import { Field } from '@/components/field';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';

export type DivisionOption = { id: number; name: string };
export type CoachOption = { id: number; name: string; role: string };

type Props = {
    errors: Record<string, string>;
    divisions: DivisionOption[];
    coaches: CoachOption[];
    idPrefix: string;
    team?: { name: string; divisionId: number; headCoachId: number | null };
};

export function TeamFields({
    errors,
    divisions,
    coaches,
    idPrefix,
    team,
}: Props) {
    return (
        <>
            <Field
                id={`${idPrefix}-name`}
                label="Team name"
                error={errors.name}
            >
                <Input
                    id={`${idPrefix}-name`}
                    name="name"
                    defaultValue={team?.name}
                    placeholder="10U Red"
                    required
                />
            </Field>
            <Field
                id={`${idPrefix}-division`}
                label="Division"
                error={errors.division_id}
            >
                <NativeSelect
                    id={`${idPrefix}-division`}
                    name="division_id"
                    defaultValue={team?.divisionId ?? ''}
                    required
                >
                    <option value="" disabled>
                        Choose a division
                    </option>
                    {divisions.map((division) => (
                        <option key={division.id} value={division.id}>
                            {division.name}
                        </option>
                    ))}
                </NativeSelect>
            </Field>
            <Field
                id={`${idPrefix}-coach`}
                label="Head coach"
                hint="Head coaches can manage this team's roster and see its parents' contact details."
                error={errors.head_coach_id}
            >
                <NativeSelect
                    id={`${idPrefix}-coach`}
                    name="head_coach_id"
                    defaultValue={team?.headCoachId ?? ''}
                >
                    <option value="">No head coach yet</option>
                    {coaches.map((coach) => (
                        <option key={coach.id} value={coach.id}>
                            {coach.name} ({coach.role})
                        </option>
                    ))}
                </NativeSelect>
            </Field>
        </>
    );
}
