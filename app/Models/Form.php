<?php

namespace App\Models;

use App\Enums\FormStatus;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A registration form. Its questions live in versions: the draft is edited in
 * place, and publishing freezes it so answers already received keep meaning
 * what they meant.
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $season_id
 * @property string $title
 * @property string $slug
 * @property FormStatus $status
 * @property int $price_cents
 * @property int|null $current_version_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'season_id', 'price_cents'])]
class Form extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<FormFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FormStatus::class,
            'price_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return HasMany<FormVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }

    /**
     * The version families see while the form is open.
     *
     * @return BelongsTo<FormVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'current_version_id');
    }

    /**
     * The unpublished version being edited, if there is one.
     *
     * @return HasOne<FormVersion, $this>
     */
    public function draftVersion(): HasOne
    {
        return $this->hasOne(FormVersion::class)->whereNull('published_at')->latestOfMany('version');
    }

    public function chargesFee(): bool
    {
        return $this->price_cents > 0;
    }

    public function hasBeenPublished(): bool
    {
        return $this->current_version_id !== null;
    }
}
