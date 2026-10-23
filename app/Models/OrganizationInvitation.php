<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * An invitation for someone to join an organization as an admin or coach.
 *
 * Not tenant-scoped: the person accepting it isn't a member yet, so
 * organization-side code always reaches invitations through the organization.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $email
 * @property OrganizationRole $role
 * @property int $invited_by
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['email', 'role', 'expires_at'])]
class OrganizationInvitation extends Model
{
    /** @use HasFactory<OrganizationInvitationFactory> */
    use HasFactory, MassPrunable;

    public const VALID_FOR_DAYS = 7;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => mb_strtolower(trim($value)));
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Invitations that expired or were accepted more than 30 days ago are
     * deleted by the scheduled model:prune.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where(fn (Builder $query) => $query
            ->where('expires_at', '<', now()->subDays(30))
            ->orWhere('accepted_at', '<', now()->subDays(30)));
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function isFor(User $user): bool
    {
        return mb_strtolower($user->email) === $this->email;
    }

    /**
     * The link sent to the invitee. It stops working when the invitation expires.
     */
    public function url(): string
    {
        return URL::temporarySignedRoute('invitations.show', $this->expires_at, ['invitation' => $this->id]);
    }
}
