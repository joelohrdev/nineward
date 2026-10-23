<?php

namespace App\Actions\Seasons;

use App\Models\Organization;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RollOverSeason
{
    /**
     * Start a new season, optionally copying teams from an earlier one.
     * Rosters are never copied: kids move up, switch teams or stop playing,
     * so every new season's rosters start empty.
     *
     * @param  array{name: string, starts_on: string, ends_on: string, age_cutoff_on: string}  $attributes
     * @param  Collection<int, Team>  $teamsToCopy
     */
    public function handle(
        Organization $organization,
        array $attributes,
        Collection $teamsToCopy,
        bool $copyHeadCoaches,
        bool $activate,
    ): Season {
        return DB::transaction(function () use ($organization, $attributes, $teamsToCopy, $copyHeadCoaches, $activate): Season {
            Organization::query()->whereKey($organization->id)->lockForUpdate()->first();

            $season = $organization->seasons()->create($attributes);

            foreach ($teamsToCopy as $team) {
                $season->teams()->make([
                    'division_id' => $team->division_id,
                    'head_coach_id' => $copyHeadCoaches ? $team->head_coach_id : null,
                    'name' => $team->name,
                    'slug' => $team->slug,
                ])->forceFill(['organization_id' => $organization->id])->save();
            }

            if ($activate) {
                $organization->forceFill(['active_season_id' => $season->id])->save();
            }

            return $season;
        });
    }
}
