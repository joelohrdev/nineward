<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Stripe account an organization collects registration fees in.
 *
 * Not tenant-scoped: Stripe webhooks arrive with only the account ID, and
 * this is how they find the organization. Organization-side code reaches it
 * through Organization::stripeConnectAccount().
 *
 * @property int $id
 * @property int $organization_id
 * @property string $stripe_account_id
 * @property bool $charges_enabled
 * @property bool $payouts_enabled
 * @property bool $details_submitted
 * @property CarbonImmutable|null $disconnected_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class StripeConnectAccount extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'charges_enabled' => 'boolean',
            'payouts_enabled' => 'boolean',
            'details_submitted' => 'boolean',
            'disconnected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function canAcceptPayments(): bool
    {
        return $this->charges_enabled && $this->disconnected_at === null;
    }
}
