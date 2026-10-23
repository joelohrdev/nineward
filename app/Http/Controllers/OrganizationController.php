<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\CreateOrganization;
use App\Http\Requests\StoreOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('organizations/create', [
            'trialDays' => config()->integer('services.stripe.trial_days'),
        ]);
    }

    public function store(StoreOrganizationRequest $request, CreateOrganization $createOrganization): RedirectResponse
    {
        $createOrganization->handle($request->user(), $request->string('name')->trim()->toString());

        return to_route('dashboard');
    }
}
