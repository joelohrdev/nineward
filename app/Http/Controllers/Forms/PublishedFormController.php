<?php

namespace App\Http\Controllers\Forms;

use App\Actions\Forms\PublishForm;
use App\Enums\FormStatus;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PublishedFormController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    /**
     * Publish the draft and open the form to families.
     */
    public function store(Form $form, PublishForm $publishForm): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $publishForm->handle($form);

        return back();
    }

    /**
     * Close the form to new registrations. Its versions stay as they are.
     */
    public function destroy(Form $form): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        if ($form->status === FormStatus::Published) {
            $form->forceFill(['status' => FormStatus::Closed])->save();
        }

        return back();
    }
}
