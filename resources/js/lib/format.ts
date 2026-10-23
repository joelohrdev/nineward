const dateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
});

const dateOnlyPattern = /^(\d{4})-(\d{2})-(\d{2})$/;

/**
 * Parses an ISO date. A date without a time, like a birthday, is read as
 * that calendar day locally rather than as midnight UTC, which would show
 * the day before for anyone west of Greenwich.
 */
function parseDate(isoDate: string): Date {
    const dateOnly = dateOnlyPattern.exec(isoDate);

    return dateOnly === null
        ? new Date(isoDate)
        : new Date(
              Number(dateOnly[1]),
              Number(dateOnly[2]) - 1,
              Number(dateOnly[3]),
          );
}

export function formatDate(isoDate: string | null): string {
    return isoDate === null ? '–' : dateFormatter.format(parseDate(isoDate));
}

/**
 * Whole days from now until the date, never below zero.
 */
export function daysUntil(isoDate: string | null): number {
    if (isoDate === null) {
        return 0;
    }

    const milliseconds = parseDate(isoDate).getTime() - Date.now();

    return Math.max(0, Math.ceil(milliseconds / 86_400_000));
}

const moneyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

export function formatMoney(cents: number): string {
    return moneyFormatter.format(cents / 100);
}
