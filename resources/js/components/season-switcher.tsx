import { Link, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { SeasonTile } from '@/components/scorebook/season-tile';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { index as teams } from '@/routes/seasons/teams';

/**
 * The season tile in the top bar. It shows the season the page is about, or
 * the one the user last picked, or the active season. Switching opens that
 * season's teams, and the app remembers the choice for the other pages.
 */
export function SeasonSwitcher() {
    const {
        seasons,
        selectedSeasonId,
        season: pageSeason,
    } = usePage<{
        season?: { id: number };
    }>().props;

    const shown =
        seasons.find((season) => season.id === pageSeason?.id) ??
        seasons.find((season) => season.id === selectedSeasonId) ??
        seasons.find((season) => season.isActive) ??
        seasons[0];

    if (shown === undefined) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <SeasonTile name={shown.name} isFinal={shown.hasEnded} />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                <DropdownMenuLabel className="label-caps text-ink-muted">
                    Seasons
                </DropdownMenuLabel>
                {seasons.map((season) => (
                    <DropdownMenuItem key={season.id} asChild>
                        <Link
                            href={teams(season.id)}
                            className="w-full cursor-pointer"
                        >
                            <span className="flex-1 truncate">
                                {season.name}
                            </span>
                            {season.isActive && (
                                <span className="label-caps text-[0.6875rem] text-ink-muted">
                                    Active
                                </span>
                            )}
                            {season.id === shown.id && (
                                <Check aria-label="Showing this season" />
                            )}
                        </Link>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
