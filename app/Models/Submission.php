<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One family's answers to a registration form.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $form_id
 * @property int $form_version_id
 * @property int|null $user_id
 * @property int|null $guardian_id
 * @property int|null $player_id
 * @property int|null $possible_duplicate_player_id
 * @property array<string, string|null> $data
 * @property SubmissionStatus $status
 * @property string|null $review_reason
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['data', 'status', 'review_reason'])]
#[Hidden(['data'])]
class Submission extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'encrypted:array',
            'status' => SubmissionStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * @return BelongsTo<FormVersion, $this>
     */
    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class);
    }

    /**
     * @return BelongsTo<Guardian, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function possibleDuplicate(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'possible_duplicate_player_id')->withTrashed();
    }

    /**
     * The registration fee, if the form charged one.
     *
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
