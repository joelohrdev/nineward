<?php

namespace App\Http\Controllers\Forms;

use App\Actions\Forms\SaveFormDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\UpdateFormDraftRequest;
use App\Models\Form;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FormDraftController extends Controller
{
    /**
     * Save the builder's questions to the form's draft.
     */
    public function update(UpdateFormDraftRequest $request, Form $form, SaveFormDraft $saveFormDraft, CurrentOrganization $currentOrganization): RedirectResponse
    {
        Gate::authorize('manageSettings', $currentOrganization->get());

        $saveFormDraft->handle($form, $request->schema());

        return back();
    }
}
