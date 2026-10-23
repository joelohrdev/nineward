<?php

namespace App\Http\Controllers\Players;

use App\Actions\Divisions\SuggestDivision;
use App\Actions\Players\CreatePlayer;
use App\Enums\Handedness;
use App\Enums\Position;
use App\Enums\ThrowingArm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Players\SavePlayerRequest;
use App\Models\Guardian;
use App\Models\Player;
use App\Models\Submission;
use App\Support\SelectedSeason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function __construct(private SelectedSeason $selectedSeason) {}

    public function index(Request $request, SuggestDivision $suggestDivision): Response
    {
        Gate::authorize('viewAny', Player::class);

        $search = $request->string('search')->trim()->toString();
        $showRemoved = $request->boolean('removed');
        $shownSeason = $this->selectedSeason->get();
        $roster = $shownSeason === null ? '' : $request->string('roster')->toString();

        $players = Player::query()
            ->when($showRemoved, fn ($query) => $query->onlyTrashed())
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when($shownSeason !== null && $roster === 'unassigned', fn ($query) => $query->unassignedFor($shownSeason))
            ->when($shownSeason !== null && $roster === 'rostered', fn ($query) => $query->rosteredIn($shownSeason))
            ->with('guardians:id,first_name,last_name')
            ->withCount('rosterEntries')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Player $player): array => [
                'id' => $player->id,
                'name' => $player->fullName(),
                'dateOfBirth' => $player->date_of_birth->toDateString(),
                'leagueAge' => $shownSeason === null ? null : $player->leagueAgeFor($shownSeason),
                'suggestedDivision' => $shownSeason === null ? null : $suggestDivision->handle($player, $shownSeason)?->name,
                'batsThrows' => $this->batsThrows($player),
                'position' => $player->primary_position?->value,
                'guardians' => $player->guardians->map(fn (Guardian $guardian): string => $guardian->fullName())->all(),
                'seasonsPlayed' => $player->roster_entries_count ?? 0,
                'isRemoved' => $player->trashed(),
            ]);

        return Inertia::render('players/index', [
            'players' => $players,
            'filters' => ['search' => $search, 'removed' => $showRemoved, 'roster' => $roster],
            'activeSeasonName' => $shownSeason?->name,
            'counts' => Inertia::defer(fn (): array => [
                'players' => Player::count(),
                'removed' => Player::onlyTrashed()->count(),
                'unclaimedGuardians' => Guardian::whereNull('user_id')->count(),
            ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Player::class);

        return Inertia::render('players/create', $this->formOptions());
    }

    public function store(SavePlayerRequest $request, CreatePlayer $createPlayer): RedirectResponse
    {
        Gate::authorize('create', Player::class);

        $player = $createPlayer->handle($request->playerAttributes(), $request->guardians());

        return to_route('players.show', $player);
    }

    public function show(Player $player, SuggestDivision $suggestDivision): Response
    {
        Gate::authorize('view', $player);

        $player->load([
            'guardians' => fn ($query) => $query->orderBy('last_name')->orderBy('first_name')->orderBy('guardians.id'),
            'rosterEntries.team' => fn ($query) => $query->withTrashed()->with('season:id,name,starts_on', 'division:id,name'),
        ]);
        $shownSeason = $this->selectedSeason->get();

        return Inertia::render('players/show', [
            'player' => [
                'id' => $player->id,
                'firstName' => $player->first_name,
                'lastName' => $player->last_name,
                'name' => $player->fullName(),
                'dateOfBirth' => $player->date_of_birth->toDateString(),
                'graduationYear' => $player->graduation_year,
                'bats' => $player->bats?->label(),
                'throws' => $player->throws?->label(),
                'position' => $player->primary_position?->label(),
                'notes' => $player->notes,
                'leagueAge' => $shownSeason === null ? null : $player->leagueAgeFor($shownSeason),
                'suggestedDivision' => $shownSeason === null ? null : $suggestDivision->handle($player, $shownSeason)?->name,
            ],
            'activeSeasonName' => $shownSeason?->name,
            'canManage' => Gate::allows('update', $player),
            'registrations' => Gate::allows('update', $player)
                ? $player->submissions()->with('form:id,title')->latest()->latest('id')->get()
                    ->map(fn (Submission $submission): array => [
                        'id' => $submission->id,
                        'formTitle' => $submission->form->title,
                        'status' => $submission->status->value,
                        'statusLabel' => $submission->status->label(),
                        'submittedAt' => $submission->created_at?->toIso8601String(),
                    ])->all()
                : [],
            'rosterHistory' => $player->rosterEntries
                ->filter(fn ($entry): bool => $entry->team !== null)
                ->sortByDesc(fn ($entry) => $entry->team->season->starts_on)
                ->values()
                ->map(fn ($entry): array => [
                    'seasonId' => $entry->team->season_id,
                    'seasonName' => $entry->team->season->name,
                    'teamName' => $entry->team->name,
                    'teamSlug' => $entry->team->trashed() ? null : $entry->team->slug,
                    'divisionName' => $entry->team->division->name,
                    'jerseyNumber' => $entry->jersey_number,
                ])->all(),
            // Anyone who may view the player may view their guardians: coaches
            // reach a player only through a team they head.
            'guardians' => $player->guardians->map(fn (Guardian $guardian): array => [
                'id' => $guardian->id,
                'firstName' => $guardian->first_name,
                'lastName' => $guardian->last_name,
                'name' => $guardian->fullName(),
                'email' => $guardian->email,
                'phone' => $guardian->phone,
                'relationship' => $guardian->pivot?->getAttribute('relationship'),
                'isClaimed' => $guardian->isClaimed(),
            ])->all(),
        ]);
    }

    public function edit(Player $player): Response
    {
        Gate::authorize('update', $player);

        return Inertia::render('players/edit', [
            ...$this->formOptions(),
            'player' => [
                'id' => $player->id,
                'name' => $player->fullName(),
                'first_name' => $player->first_name,
                'last_name' => $player->last_name,
                'date_of_birth' => $player->date_of_birth->toDateString(),
                'graduation_year' => $player->graduation_year,
                'bats' => $player->bats?->value,
                'throws' => $player->throws?->value,
                'primary_position' => $player->primary_position?->value,
                'notes' => $player->notes,
            ],
        ]);
    }

    public function update(SavePlayerRequest $request, Player $player): RedirectResponse
    {
        Gate::authorize('update', $player);

        $player->update($request->playerAttributes());

        return to_route('players.show', $player);
    }

    /**
     * Remove a player from the master list. They can be restored later.
     */
    public function destroy(Player $player): RedirectResponse
    {
        Gate::authorize('delete', $player);

        $player->delete();

        return to_route('players.index');
    }

    private function batsThrows(Player $player): ?string
    {
        if ($player->bats === null && $player->throws === null) {
            return null;
        }

        return ($player->bats->value ?? '–').'/'.($player->throws->value ?? '–');
    }

    /**
     * @return array{handedness: list<array{value: string, label: string}>, throwingArms: list<array{value: string, label: string}>, positions: list<array{value: string, label: string}>}
     */
    private function formOptions(): array
    {
        return [
            'handedness' => array_map(fn (Handedness $case): array => ['value' => $case->value, 'label' => $case->label()], Handedness::cases()),
            'throwingArms' => array_map(fn (ThrowingArm $case): array => ['value' => $case->value, 'label' => $case->label()], ThrowingArm::cases()),
            'positions' => array_map(fn (Position $case): array => ['value' => $case->value, 'label' => $case->label()], Position::cases()),
        ];
    }
}
