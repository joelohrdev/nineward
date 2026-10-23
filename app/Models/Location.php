<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A field or facility the organization plays or practices at.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $address
 * @property string|null $maps_url
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'address', 'maps_url'])]
class Location extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<LocationFactory> */
    use HasFactory;
}
