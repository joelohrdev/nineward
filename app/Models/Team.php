<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A team in one season, such as "10U Red" in Spring 2026. Next season's
 * "10U Red" is a different team.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $season_id
 * @property int $division_id
 * @property int|null $head_coach_id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read RosterEntry|null $rosterEntry
 * @property-read int|null $players_count
 */
#[Fillable(['name', 'slug', 'division_id', 'head_coach_id'])]
class Team extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<TeamFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function headCoach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_coach_id');
    }

    /**
     * @return BelongsToMany<Player, $this, RosterEntry, 'rosterEntry'>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'team_player')
            ->using(RosterEntry::class)
            ->as('rosterEntry')
            ->withPivot('id', 'season_id', 'jersey_number', 'position', 'is_captain')
            ->withTimestamps();
    }

    /**
     * @return HasMany<RosterEntry, $this>
     */
    public function rosterEntries(): HasMany
    {
        return $this->hasMany(RosterEntry::class);
    }

    public function isHeadedBy(User $user): bool
    {
        return $this->head_coach_id === $user->id;
    }
}
