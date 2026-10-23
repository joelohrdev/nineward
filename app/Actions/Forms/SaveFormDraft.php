<?php

namespace App\Actions\Forms;

use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Support\Facades\DB;

class SaveFormDraft
{
    /**
     * Save the questions being edited. Once a form is published its versions
     * are frozen, so the first edit afterwards starts a new draft version.
     *
     * @param  array{fields: list<array<string, mixed>>}  $schema
     */
    public function handle(Form $form, array $schema): FormVersion
    {
        return DB::transaction(function () use ($form, $schema): FormVersion {
            Form::query()->whereKey($form->id)->lockForUpdate()->first();

            $draft = $form->draftVersion()->first();

            if ($draft === null) {
                $draft = $form->versions()->create([
                    'version' => (int) $form->versions()->max('version') + 1,
                    'schema' => $schema,
                ]);
            } else {
                $draft->update(['schema' => $schema]);
            }

            return $draft;
        });
    }
}
