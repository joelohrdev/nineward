import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type Paginated<Row> = {
    data: Row[];
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export function Pagination({ page }: { page: Paginated<unknown> }) {
    if (page.last_page <= 1) {
        return null;
    }

    return (
        <nav aria-label="Pages" className="flex items-center justify-between">
            {page.prev_page_url ? (
                <Button asChild variant="outline">
                    <Link href={page.prev_page_url}>Previous</Link>
                </Button>
            ) : (
                <span />
            )}
            <span className="label-caps text-ink-muted">
                Page {page.current_page} of {page.last_page}
            </span>
            {page.next_page_url ? (
                <Button asChild variant="outline">
                    <Link href={page.next_page_url}>Next</Link>
                </Button>
            ) : (
                <span />
            )}
        </nav>
    );
}
