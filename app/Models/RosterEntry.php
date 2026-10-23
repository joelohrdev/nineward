<?php

namespace App\Models;

use App\Enums\Position;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A player's place on a team for one season, with their seasonal details.
 *
 * @property int $id
 * @property int $team_id
 * @property int $season_id
 * @property int $player_id
 * @property int|null $jersey_number
 * @property Position|null $position
 * @property bool $is_captain
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table(name: 'team_player', incrementing: true)]
/*
 * season_id is set when a player is attached and copied from the team. It's
 * never in any form request, so it can't come from the client.
 */
#[Fillable(['season_id', 'jersey_number', 'position', 'is_captain'])]
class RosterEntry extends Pivot
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jersey_number' => 'integer',
            'position' => Position::class,
            'is_captain' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
