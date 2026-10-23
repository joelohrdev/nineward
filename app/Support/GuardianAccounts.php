<?php

namespace App\Support;

use App\Models\Guardian;
use App\Models\Player;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The parent side of guardian records. Parents aren't members of the
 * organizations their kids play for, so these lookups cross organizations,
 * but every one of them is limited to the signed-in user's own guardian
 * records or to a single guardian named by a signed claim link.
 */
class GuardianAccounts
{
    /**
     * Find the guardian named in a signed claim link.
     */
    public function findForClaim(int $guardianId): Guardian
    {
        return Guardian::withoutGlobalScope(OrganizationScope::class)
            ->with('organization')
            ->findOrFail($guardianId);
    }

    /**
     * Link the guardian record to the user's account. Only a verified account
     * with the same email can claim it.
     */
    public function claim(Guardian $guardian, User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['claim' => 'Verify your email address before claiming.']);
        }

        if (mb_strtolower($user->email) !== $guardian->email) {
            throw ValidationException::withMessages(['claim' => "This link was sent to {$guardian->email}."]);
        }

        if ($guardian->user_id !== null && $guardian->user_id !== $user->id) {
            throw ValidationException::withMessages(['claim' => 'This guardian is already linked to another account.']);
        }

        $guardian->user()->associate($user);
        $guardian->save();
    }

    public function hasClaimed(User $user): bool
    {
        return Guardian::withoutGlobalScope(OrganizationScope::class)->whereBelongsTo($user)->exists();
    }

    /**
     * The players the user is a claimed guardian of, in every organization.
     *
     * @return Collection<int, Player>
     */
    public function playersFor(User $user): Collection
    {
        return Player::withoutGlobalScope(OrganizationScope::class)
            ->whereHas('guardians', fn ($guardians) => $guardians
                ->withoutGlobalScope(OrganizationScope::class)
                ->whereBelongsTo($user))
            ->with('organization:id,name,primary_color')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('id')
            ->get();
    }
}
