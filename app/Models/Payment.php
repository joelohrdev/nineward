<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * A registration fee, collected by Stripe Checkout in the organization's own
 * Stripe account. Its status follows Stripe's webhooks.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $submission_id
 * @property int $amount_cents
 * @property string $currency
 * @property PaymentStatus $status
 * @property int $refunded_cents
 * @property string|null $stripe_checkout_session_id
 * @property string|null $stripe_payment_intent_id
 * @property string|null $dispute_status
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Payment extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const PAY_LINK_VALID_FOR_DAYS = 30;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'refunded_cents' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * A link families can use to pay later, for example after leaving Checkout.
     */
    public function payUrl(Organization $organization): string
    {
        return URL::temporarySignedRoute(
            'registration-payments.show',
            now()->addDays(self::PAY_LINK_VALID_FOR_DAYS),
            ['organization' => $organization->slug, 'payment' => $this->id],
        );
    }
}
