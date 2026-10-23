import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';

/**
 * A label, a control and its error message, stacked.
 */
export function Field({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && !error && <p className="text-sm text-ink-muted">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
