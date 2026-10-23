<?php

namespace App\Models;

use App\Enums\Handedness;
use App\Enums\Position;
use App\Enums\ThrowingArm;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A kid in the organization's master list. Players persist across seasons;
 * their seasonal details live on roster entries.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $first_name
 * @property string $last_name
 * @property CarbonImmutable $date_of_birth
 * @property int|null $graduation_year
 * @property Handedness|null $bats
 * @property ThrowingArm|null $throws
 * @property Position|null $primary_position
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read int|null $roster_entries_count
 * @property-read RosterEntry|null $rosterEntry
 */
#[Fillable(['first_name', 'last_name', 'date_of_birth', 'graduation_year', 'bats', 'throws', 'primary_position', 'notes'])]
class Player extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<PlayerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'graduation_year' => 'integer',
            'bats' => Handedness::class,
            'throws' => ThrowingArm::class,
            'primary_position' => Position::class,
        ];
    }

    /**
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)->withPivot('relationship')->withTimestamps();
    }

    /**
     * @return HasMany<RosterEntry, $this>
     */
    public function rosterEntries(): HasMany
    {
        return $this->hasMany(RosterEntry::class);
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Players not on any team in the season.
     *
     * @param  Builder<Player>  $query
     */
    #[Scope]
    protected function unassignedFor(Builder $query, Season $season): void
    {
        $query->whereDoesntHave('rosterEntries', fn (Builder $entries) => $entries->where('season_id', $season->id));
    }

    /**
     * Players on a team in the season.
     *
     * @param  Builder<Player>  $query
     */
    #[Scope]
    protected function rosteredIn(Builder $query, Season $season): void
    {
        $query->whereHas('rosterEntries', fn (Builder $entries) => $entries->where('season_id', $season->id));
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * The player's age on the season's age cutoff date.
     */
    public function leagueAgeFor(Season $season): int
    {
        $interval = $this->date_of_birth->diff($season->age_cutoff_on);

        return $interval->invert === 1 ? 0 : $interval->y;
    }

    /**
     * Players whose name or a guardian's name or email matches the search.
     *
     * @param  Builder<Player>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $words = array_filter(preg_split('/\s+/', mb_strtolower(trim($term))) ?: []);

        foreach ($words as $word) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word).'%';

            $query->where(function (Builder $query) use ($like): void {
                $query->whereRaw('lower(first_name) like ?', [$like])
                    ->orWhereRaw('lower(last_name) like ?', [$like])
                    ->orWhereHas('guardians', fn (Builder $guardians) => $guardians
                        ->whereRaw('lower(first_name) like ?', [$like])
                        ->orWhereRaw('lower(last_name) like ?', [$like])
                        ->orWhereRaw('lower(email) like ?', [$like]));
            });
        }
    }
}
