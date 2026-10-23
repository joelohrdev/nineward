import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    title: string;
    description?: string;
    trigger: ReactNode;
    form: { action: string; method: 'get' | 'post' };
    submitLabel: string;
    children: (errors: Record<string, string>) => ReactNode;
};

/**
 * A dialog holding one form. It closes itself when the save succeeds.
 */
export function FormDialog({
    title,
    description,
    trigger,
    form,
    submitLabel,
    children,
}: Props) {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                {description && (
                    <DialogDescription>{description}</DialogDescription>
                )}
                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setIsOpen(false)}
                    className="grid gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            {children(errors)}
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
