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

type Props = {
    title: string;
    description: string;
    confirmLabel: string;
    trigger: ReactNode;
    form: { action: string; method: 'get' | 'post' };
};

/**
 * Asks before a destructive action and shows any error the server returns.
 */
export function ConfirmDialog({
    title,
    description,
    confirmLabel,
    trigger,
    form,
}: Props) {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setIsOpen(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            {Object.values(errors).map((error) => (
                                <p
                                    key={error}
                                    className="mb-4 text-sm font-medium text-danger"
                                >
                                    {error}
                                </p>
                            ))}
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {confirmLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
