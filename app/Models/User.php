<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_organization_id
 * @property bool $is_platform_admin
 * @property-read Membership|null $membership
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_platform_admin' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Organization, $this, Membership, 'membership'>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(Membership::class)
            ->as('membership')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    /**
     * The user's role in the given organization, or null when they are not a member.
     */
    public function roleIn(Organization $organization): ?OrganizationRole
    {
        $membership = Membership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $this->id)
            ->first();

        return $membership?->role;
    }

    public function switchOrganization(Organization $organization): void
    {
        $this->forceFill(['current_organization_id' => $organization->id])->save();
    }
}
