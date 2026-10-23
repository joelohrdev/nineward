import type { StatusTone } from '@/components/scorebook/status-mark';

export type SubmissionStatus = 'processed' | 'needs_review' | 'dismissed';

export const submissionTones: Record<SubmissionStatus, StatusTone> = {
    processed: 'done',
    needs_review: 'flagged',
    dismissed: 'neutral',
};
