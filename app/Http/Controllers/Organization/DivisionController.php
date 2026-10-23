<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\SaveDivisionRequest;
use App\Models\Division;
use App\Models\Team;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DivisionController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function index(): Response
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        return Inertia::render('organization/divisions', [
            'divisions' => Division::query()
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
                ->map(fn (Division $division): array => [
                    'id' => $division->id,
                    'name' => $division->name,
                    'minAge' => $division->min_age,
                    'maxAge' => $division->max_age,
                ])
                ->all(),
        ]);
    }

    public function store(SaveDivisionRequest $request): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        Division::create([
            ...$request->validated(),
            'display_order' => (int) Division::max('display_order') + 1,
        ]);

        return to_route('organization.divisions.index');
    }

    public function update(SaveDivisionRequest $request, Division $division): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $division->update($request->validated());

        return to_route('organization.divisions.index');
    }

    public function destroy(Division $division): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        if (Team::withTrashed()->whereBelongsTo($division)->exists()) {
            throw ValidationException::withMessages(['division' => 'Teams use this division, so it can\'t be deleted. Move those teams to another division first.']);
        }

        $division->delete();

        return to_route('organization.divisions.index');
    }
}
