/**
 * The registration form schema, matching App\Forms\FormSchema.
 */
export type FieldType =
    | 'short_text'
    | 'long_text'
    | 'email'
    | 'phone'
    | 'number'
    | 'date'
    | 'select'
    | 'yes_no'
    | 'section';

export type FormField = {
    id: string;
    type: FieldType;
    label: string;
    help: string | null;
    required: boolean;
    options: string[];
    maps_to: string | null;
};

export type FieldMappingOption = {
    value: string;
    label: string;
    type: FieldType;
    options: string[];
    isCore: boolean;
    group: string;
};

export function newFieldId(): string {
    const alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
    const bytes = crypto.getRandomValues(new Uint8Array(8));

    return `f_${Array.from(bytes, (byte) => alphabet[byte % alphabet.length]).join('')}`;
}

export function collectsAnswer(type: FieldType): boolean {
    return type !== 'section';
}
