<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A time container for teams and rosters, such as "Spring 2026".
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property CarbonImmutable $age_cutoff_on
 * @property bool $is_registration_open
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'starts_on', 'ends_on', 'age_cutoff_on', 'is_registration_open'])]
class Season extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'age_cutoff_on' => 'date',
            'is_registration_open' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function hasEnded(): bool
    {
        return $this->ends_on->endOfDay()->isPast();
    }
}
