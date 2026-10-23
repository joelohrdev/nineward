<?php

namespace App\Models;

use Database\Factories\PlatformAuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A record of something a platform admin did, kept for accountability.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $organization_id
 * @property string $action
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
#[Fillable(['user_id', 'organization_id', 'action', 'reason', 'metadata'])]
class PlatformAuditLog extends Model
{
    /** @use HasFactory<PlatformAuditLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function record(User $user, string $action, ?Organization $organization = null, ?string $reason = null, ?array $metadata = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'organization_id' => $organization?->id,
            'action' => $action,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }
}
