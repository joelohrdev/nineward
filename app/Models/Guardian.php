<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A parent or guardian's contact details. A guardian is "unclaimed" until a
 * user with the same verified email links it to their account.
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Pivot|null $pivot
 */
#[Fillable(['first_name', 'last_name', 'email', 'phone'])]
class Guardian extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<GuardianFactory> */
    use HasFactory;

    /**
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => mb_strtolower(trim($value)));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class)->withPivot('relationship')->withTimestamps();
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isClaimed(): bool
    {
        return $this->user_id !== null;
    }
}
