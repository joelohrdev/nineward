<?php

namespace App\Actions\Forms;

use App\Enums\FormStatus;
use App\Models\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishForm
{
    /**
     * Open the form to families. A pending draft is frozen and becomes the
     * version they fill in; with no draft, a closed form simply reopens.
     */
    public function handle(Form $form): void
    {
        DB::transaction(function () use ($form): void {
            $form = Form::query()->whereKey($form->id)->lockForUpdate()->firstOrFail();
            $draft = $form->draftVersion()->first();

            if ($form->chargesFee() && ! $form->organization->canAcceptPayments()) {
                throw ValidationException::withMessages(['form' => 'Connect your Stripe account before opening a form with a fee.']);
            }

            if ($draft === null && ! $form->hasBeenPublished()) {
                throw ValidationException::withMessages(['form' => 'Add some questions before publishing.']);
            }

            if ($draft !== null) {
                $draft->forceFill(['published_at' => now()])->save();
                $form->current_version_id = $draft->id;
            }

            $form->status = FormStatus::Published;
            $form->save();
        });
    }
}
