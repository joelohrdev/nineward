<?php

namespace App\Actions\Guardians;

use App\Models\Guardian;

/**
 * The one rule for matching guardians: the same email address within an
 * organization is the same person. Existing details are never overwritten,
 * but a missing phone number is filled in.
 */
class FindOrCreateGuardian
{
    /**
     * @param  array{first_name: string, last_name: string, email: string, phone?: string|null}  $details
     */
    public function handle(array $details): Guardian
    {
        $guardian = Guardian::query()->firstOrNew(['email' => mb_strtolower(trim($details['email']))]);

        if (! $guardian->exists) {
            $guardian->fill($details)->save();

            return $guardian;
        }

        if ($guardian->phone === null && ($details['phone'] ?? null) !== null) {
            $guardian->phone = $details['phone'];
            $guardian->save();
        }

        return $guardian;
    }
}
