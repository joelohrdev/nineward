<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\DivisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An age group or skill level, such as "10U". Divisions persist across seasons.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property int|null $min_age
 * @property int|null $max_age
 * @property int $display_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'min_age', 'max_age', 'display_order'])]
class Division extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<DivisionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'display_order' => 'integer',
        ];
    }
}
