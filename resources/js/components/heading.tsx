import { SectionHeading } from '@/components/scorebook/section-heading';

export default function Heading({
    title,
    description,
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return <SectionHeading title={title} description={description} />;
}
