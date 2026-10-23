<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Divisions\SuggestDivision;
use App\Actions\Teams\SaveTeam;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\SaveTeamRequest;
use App\Models\Division;
use App\Models\Guardian;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\SelectedSeason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function index(Request $request, Season $season, SelectedSeason $selectedSeason): Response
    {
        Gate::authorize('viewAny', Team::class);
        $selectedSeason->remember($season);

        $user = $request->user();
        $canManageTeams = Gate::allows('create', Team::class);

        $teams = $season->teams()
            ->with(['division:id,name,display_order', 'headCoach:id,name'])
            ->withCount('players')
            ->when(! $canManageTeams, fn ($query) => $query->where('head_coach_id', $user->id))
            ->get()
            ->sortBy([
                fn (Team $a, Team $b) => $a->division->display_order <=> $b->division->display_order,
                fn (Team $a, Team $b) => strcmp($a->name, $b->name),
            ])
            ->values();

        return Inertia::render('teams/index', [
            'season' => $this->seasonSummary($season),
            'teams' => $teams->map(fn (Team $team): array => [
                'id' => $team->id,
                'slug' => $team->slug,
                'name' => $team->name,
                'divisionId' => $team->division_id,
                'divisionName' => $team->division->name,
                'headCoachId' => $team->head_coach_id,
                'headCoachName' => $team->headCoach?->name,
                'playersCount' => $team->players_count,
            ])->all(),
            'unassignedCount' => $canManageTeams ? Player::unassignedFor($season)->count() : null,
            'canManageTeams' => $canManageTeams,
            'divisions' => $canManageTeams ? $this->divisionOptions() : [],
            'coaches' => $canManageTeams ? $this->coachOptions() : [],
        ]);
    }

    public function store(SaveTeamRequest $request, Season $season, SaveTeam $saveTeam): RedirectResponse
    {
        Gate::authorize('create', Team::class);

        $team = $saveTeam->handle($season, $request->teamAttributes());

        return to_route('seasons.teams.show', [$season, $team]);
    }

    public function show(Request $request, Season $season, Team $team, SuggestDivision $suggestDivision, SelectedSeason $selectedSeason): Response
    {
        Gate::authorize('view', $team);
        $selectedSeason->remember($season);

        $team->load(['division', 'headCoach:id,name']);
        $canManageRoster = Gate::allows('manageRoster', $team);
        $canSeeContacts = Gate::allows('exportRoster', $team);

        $roster = $team->players()
            ->with($canSeeContacts ? ['guardians:id,first_name,last_name,email,phone'] : [])
            ->orderByRaw('team_player.jersey_number is null')
            ->orderBy('team_player.jersey_number')
            ->orderBy('players.last_name')
            ->orderBy('players.id')
            ->get();

        $pool = $canManageRoster
            ? Player::unassignedFor($season)->orderBy('last_name')->orderBy('first_name')->orderBy('id')->get()
            : collect();

        return Inertia::render('teams/show', [
            'season' => $this->seasonSummary($season),
            'team' => [
                'id' => $team->id,
                'slug' => $team->slug,
                'name' => $team->name,
                'divisionId' => $team->division_id,
                'divisionName' => $team->division->name,
                'headCoachId' => $team->head_coach_id,
                'headCoachName' => $team->headCoach?->name,
            ],
            'roster' => $roster->map(fn (Player $player): array => [
                'id' => $player->rosterEntry?->id,
                'playerId' => $player->id,
                'name' => $player->fullName(),
                'leagueAge' => $player->leagueAgeFor($season),
                'suggestedDivision' => $suggestDivision->handle($player, $season)?->name,
                'jerseyNumber' => $player->rosterEntry?->jersey_number,
                'position' => $player->rosterEntry?->position?->value,
                'isCaptain' => $player->rosterEntry->is_captain ?? false,
                'guardians' => $canSeeContacts ? $player->guardians->map(fn (Guardian $guardian): array => [
                    'name' => $guardian->fullName(),
                    'email' => $guardian->email,
                    'phone' => $guardian->phone,
                ])->all() : [],
            ])->all(),
            'pool' => $pool->map(fn (Player $player): array => [
                'id' => $player->id,
                'name' => $player->fullName(),
                'leagueAge' => $player->leagueAgeFor($season),
                'suggestedDivision' => $suggestDivision->handle($player, $season)?->name,
                'fitsDivision' => $this->fits($team->division, $player->leagueAgeFor($season)),
            ])->all(),
            'otherTeams' => $canManageRoster
                ? $season->teams()->whereKeyNot($team->id)->orderBy('name')->orderBy('id')->get(['id', 'name'])
                    ->map(fn (Team $other): array => ['id' => $other->id, 'name' => $other->name])->all()
                : [],
            'canManageRoster' => $canManageRoster,
            'canExport' => $canSeeContacts,
            'canManageTeam' => Gate::allows('update', $team),
            'divisions' => Gate::allows('update', $team) ? $this->divisionOptions() : [],
            'coaches' => Gate::allows('update', $team) ? $this->coachOptions() : [],
        ]);
    }

    public function update(SaveTeamRequest $request, Season $season, Team $team, SaveTeam $saveTeam): RedirectResponse
    {
        Gate::authorize('update', $team);

        $team = $saveTeam->handle($season, $request->teamAttributes(), $team);

        return to_route('seasons.teams.show', [$season, $team]);
    }

    /**
     * Delete an empty team. Players have to be moved or removed first, so
     * nobody silently loses their place for the season.
     */
    public function destroy(Season $season, Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        if ($team->rosterEntries()->exists()) {
            throw ValidationException::withMessages(['team' => 'Move or remove the players on this team before deleting it.']);
        }

        $team->delete();

        return to_route('seasons.teams.index', $season);
    }

    private function fits(Division $division, int $leagueAge): bool
    {
        return ($division->min_age === null || $division->min_age <= $leagueAge)
            && ($division->max_age === null || $division->max_age >= $leagueAge);
    }

    /**
     * @return array{id: int, name: string, hasEnded: bool}
     */
    private function seasonSummary(Season $season): array
    {
        return ['id' => $season->id, 'name' => $season->name, 'hasEnded' => $season->hasEnded()];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function divisionOptions(): array
    {
        return array_values(Division::query()->orderBy('display_order')->orderBy('id')->get(['id', 'name'])
            ->map(fn (Division $division): array => ['id' => $division->id, 'name' => $division->name])
            ->all());
    }

    /**
     * Members who can head a team: coaches, admins and the owner.
     *
     * @return list<array{id: int, name: string, role: string}>
     */
    private function coachOptions(): array
    {
        $organization = $this->currentOrganization->get();

        if ($organization === null) {
            return [];
        }

        return array_values($organization->members()->orderBy('users.name')->orderBy('users.id')->get(['users.id', 'users.name'])
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => ($member->membership->role ?? OrganizationRole::Coach)->label(),
            ])
            ->all());
    }
}
