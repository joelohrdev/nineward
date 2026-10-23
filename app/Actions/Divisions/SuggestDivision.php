<?php

namespace App\Actions\Divisions;

use App\Models\Division;
use App\Models\Player;
use App\Models\Season;
use Illuminate\Database\Eloquent\Collection;

/**
 * Suggests the division a player's league age puts them in: the youngest
 * division whose age range includes them. Divisions without an oldest age,
 * like an open "Varsity", are only suggested when no bounded division fits.
 *
 * The organization's divisions are loaded once per instance, so suggesting
 * for a whole list of players runs a single query.
 */
class SuggestDivision
{
    /** @var Collection<int, Division>|null */
    private ?Collection $divisions = null;

    public function handle(Player $player, Season $season): ?Division
    {
        return $this->forAge($player->leagueAgeFor($season));
    }

    public function forAge(int $leagueAge): ?Division
    {
        return $this->divisions()->first(fn (Division $division): bool => ($division->min_age === null || $division->min_age <= $leagueAge)
            && ($division->max_age === null || $division->max_age >= $leagueAge));
    }

    /**
     * @return Collection<int, Division>
     */
    private function divisions(): Collection
    {
        return $this->divisions ??= Division::query()
            ->orderByRaw('max_age is null')
            ->orderBy('max_age')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }
}
