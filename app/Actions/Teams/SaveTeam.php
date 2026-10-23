<?php

namespace App\Actions\Teams;

use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Str;

class SaveTeam
{
    /**
     * Create or update a team. Its URL slug follows its name.
     *
     * @param  array{name: string, division_id: int, head_coach_id: int|null}  $attributes
     */
    public function handle(Season $season, array $attributes, ?Team $team = null): Team
    {
        $team ??= new Team;
        $team->fill([...$attributes, 'slug' => Str::slug($attributes['name'])]);

        if (! $team->exists) {
            $team->season()->associate($season);
        }

        $team->save();

        return $team;
    }
}
