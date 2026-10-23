export function SectionHeading({
    title,
    description,
}: {
    title: string;
    description?: string;
}) {
    return (
        <header className="border-b border-ink pb-2">
            <h2 className="label-caps text-[0.9375rem] text-ink">{title}</h2>
            {description && (
                <p className="mt-1.5 text-sm text-ink-muted">{description}</p>
            )}
        </header>
    );
}
