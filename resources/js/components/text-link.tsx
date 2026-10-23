import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof Link>;

export default function TextLink({
    className = '',
    children,
    ...props
}: Props) {
    return (
        <Link
            className={cn(
                'font-semibold text-ink underline decoration-clay decoration-2 underline-offset-4 transition-colors hover:decoration-ink',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
