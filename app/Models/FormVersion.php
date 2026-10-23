<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FormVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One version of a form's questions. Published versions never change.
 *
 * @property int $id
 * @property int $form_id
 * @property int $version
 * @property array{fields: list<array<string, mixed>>} $schema
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['version', 'schema'])]
class FormVersion extends Model
{
    /** @use HasFactory<FormVersionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
