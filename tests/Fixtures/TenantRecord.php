<?php

namespace Tests\Fixtures;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A minimal tenant-owned model for testing BelongsToOrganization before the
 * application has tenant models of its own.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 */
#[Fillable(['name', 'organization_id'])]
class TenantRecord extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;
}
