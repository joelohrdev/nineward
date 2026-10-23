<?php

namespace App\Http\Controllers\Forms;

use App\Actions\Forms\CreateForm;
use App\Forms\FieldMapping;
use App\Forms\FieldType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\SaveFormRequest;
use App\Models\Form;
use App\Models\Season;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FormController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function index(): Response
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $forms = Form::query()
            ->with(['season:id,name', 'currentVersion:id,version', 'draftVersion:id,form_id,version'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('forms/index', [
            'forms' => $forms->map(fn (Form $form): array => [
                'id' => $form->id,
                'title' => $form->title,
                'status' => $form->status->value,
                'statusLabel' => $form->status->label(),
                'seasonName' => $form->season?->name,
                'publishedVersion' => $form->currentVersion?->version,
                'hasUnpublishedChanges' => $form->draftVersion !== null && $form->hasBeenPublished(),
            ])->all(),
            'seasonOptions' => $this->seasonOptions(),
        ]);
    }

    public function store(SaveFormRequest $request, CreateForm $createForm): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $form = $createForm->handle(
            $request->string('title')->trim()->toString(),
            $request->filled('season_id') ? $request->integer('season_id') : null,
        );

        return to_route('forms.edit', $form);
    }

    /**
     * The builder.
     */
    public function edit(Form $form): Response
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $form->load(['season:id,name', 'currentVersion', 'draftVersion']);
        $editing = $form->draftVersion ?? $form->currentVersion;

        return Inertia::render('forms/edit', [
            'form' => [
                'id' => $form->id,
                'title' => $form->title,
                'slug' => $form->slug,
                'seasonId' => $form->season_id,
                'seasonName' => $form->season?->name,
                'status' => $form->status->value,
                'statusLabel' => $form->status->label(),
                'publishedVersion' => $form->currentVersion?->version,
                'hasUnpublishedChanges' => $form->draftVersion !== null,
                'priceCents' => $form->price_cents,
                'canAcceptPayments' => $this->currentOrganization->get()?->canAcceptPayments() ?? false,
                'publicUrl' => $form->hasBeenPublished()
                    ? route('registration.show', [$this->currentOrganization->get()?->slug, $form->slug])
                    : null,
            ],
            'fields' => $editing === null ? [] : $editing->schema['fields'],
            'fieldTypes' => array_map(fn (FieldType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ], FieldType::cases()),
            'mappings' => array_map(fn (FieldMapping $mapping): array => [
                'value' => $mapping->value,
                'label' => $mapping->defaultLabel(),
                'type' => $mapping->type()->value,
                'options' => array_values($mapping->options()),
                'isCore' => $mapping->isCore(),
                'group' => str_starts_with($mapping->value, 'player.') ? 'Player' : 'Parent or guardian',
            ], FieldMapping::cases()),
            'seasonOptions' => $this->seasonOptions(),
        ]);
    }

    public function update(SaveFormRequest $request, Form $form): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $form->update([
            'title' => $request->string('title')->trim()->toString(),
            'season_id' => $request->filled('season_id') ? $request->integer('season_id') : null,
            'price_cents' => $request->priceCents(),
        ]);

        return back();
    }

    /**
     * Delete a form that was never opened to families. Published forms are
     * closed instead, so their history stays.
     */
    public function destroy(Form $form): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        if ($form->hasBeenPublished()) {
            throw ValidationException::withMessages(['form' => 'Families have seen this form, so it can only be closed, not deleted.']);
        }

        $form->delete();

        return to_route('forms.index');
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function seasonOptions(): array
    {
        return array_values(Season::query()->orderByDesc('starts_on')->orderByDesc('id')->get(['id', 'name'])
            ->map(fn (Season $season): array => ['id' => $season->id, 'name' => $season->name])
            ->all());
    }
}
