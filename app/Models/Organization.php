<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Billable;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $owner_id
 * @property string|null $logo_path
 * @property string|null $primary_color
 * @property bool $show_public_rosters
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property CarbonImmutable|null $trial_ends_at
 * @property int|null $active_season_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Membership|null $membership
 * @property-read Season|null $activeSeason
 * @property-read StripeConnectAccount|null $stripeConnectAccount
 */
#[Fillable(['name', 'slug', 'logo_path', 'primary_color', 'show_public_rosters'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use Billable, HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'show_public_rosters' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsToMany<User, $this, Membership, 'membership'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(Membership::class)
            ->as('membership')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Season, $this>
     */
    public function activeSeason(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'active_season_id');
    }

    /**
     * @return HasMany<Season, $this>
     */
    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    /**
     * @return HasOne<StripeConnectAccount, $this>
     */
    public function stripeConnectAccount(): HasOne
    {
        return $this->hasOne(StripeConnectAccount::class);
    }

    /**
     * Whether families can pay this organization's registration fees.
     */
    public function canAcceptPayments(): bool
    {
        return $this->stripeConnectAccount?->canAcceptPayments() ?? false;
    }

    /**
     * @return HasMany<Form, $this>
     */
    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    /**
     * @return HasMany<OrganizationInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    /**
     * The URL of the uploaded logo, if there is one.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path === null ? null : Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Stripe customers are named after the organization and billed to its owner.
     */
    public function stripeName(): string
    {
        return $this->name;
    }

    public function stripeEmail(): ?string
    {
        return $this->owner->email;
    }

    /**
     * Whether the organization may use the app: on its free trial, or with a
     * subscription that is active, trialing or in its cancellation grace period.
     */
    public function hasAccess(): bool
    {
        return $this->onGenericTrial() || $this->subscribed();
    }

    public function addMember(User $user, OrganizationRole $role): void
    {
        $this->members()->attach($user, ['role' => $role]);
    }
}
