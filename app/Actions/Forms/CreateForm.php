<?php

namespace App\Actions\Forms;

use App\Enums\FormStatus;
use App\Forms\FormSchema;
use App\Models\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateForm
{
    /**
     * Create a draft form with the core identity questions already in place.
     */
    public function handle(string $title, ?int $seasonId): Form
    {
        return DB::transaction(function () use ($title, $seasonId): Form {
            $form = new Form(['title' => $title, 'season_id' => $seasonId]);
            $form->slug = $this->uniqueSlug($title);
            $form->status = FormStatus::Draft;
            $form->price_cents = 0;
            $form->save();

            $form->versions()->create(['version' => 1, 'schema' => FormSchema::starter()]);

            return $form;
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'registration';
        $slug = $base;
        $suffix = 2;

        while (Form::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
