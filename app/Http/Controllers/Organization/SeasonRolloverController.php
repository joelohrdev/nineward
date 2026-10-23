<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Divisions\SuggestDivision;
use App\Actions\Seasons\RollOverSeason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreSeasonRolloverRequest;
use App\Models\Division;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Support\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SeasonRolloverController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    /**
     * The "start a new season" wizard. Asking for a preview recalculates which
     * returning players move up a division under the new age cutoff.
     */
    public function create(Request $request, SuggestDivision $suggestDivision): Response
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $seasons = Season::query()
            ->with(['teams' => fn ($query) => $query->with('division:id,name,display_order', 'headCoach:id,name')->orderBy('name')->orderBy('id')])
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();
        $latest = $seasons->first();

        return Inertia::render('organization/season-rollover', [
            'sourceSeasons' => $seasons->map(fn (Season $season): array => [
                'id' => $season->id,
                'name' => $season->name,
                'teams' => $season->teams->map(fn (Team $team): array => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'divisionName' => $team->division->name,
                    'headCoachName' => $team->headCoach?->name,
                ])->all(),
            ])->all(),
            'defaults' => [
                'sourceSeasonId' => $latest?->id,
                'startsOn' => $latest?->starts_on->addYear()->toDateString(),
                'endsOn' => $latest?->ends_on->addYear()->toDateString(),
                'ageCutoffOn' => $latest?->age_cutoff_on->addYear()->toDateString(),
            ],
            'preview' => Inertia::optional(fn (): ?array => $this->preview($request, $suggestDivision)),
        ]);
    }

    public function store(StoreSeasonRolloverRequest $request, RollOverSeason $rollOverSeason): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageSettings', $organization);

        $teamsToCopy = Team::query()->whereKey($request->teamIds())->get();

        $season = $rollOverSeason->handle(
            $organization,
            $request->seasonAttributes(),
            $teamsToCopy,
            $request->boolean('copy_head_coaches'),
            $request->boolean('activate'),
        );

        return to_route('seasons.teams.index', $season);
    }

    /**
     * Players rostered in the source season whose suggested division changes
     * under the new age cutoff.
     *
     * @return array{returningCount: int, movingUp: list<array{name: string, from: string|null, to: string|null}>}|null
     */
    private function preview(Request $request, SuggestDivision $suggestDivision): ?array
    {
        $source = Season::find($request->integer('source_season_id'));
        $cutoff = $request->date('age_cutoff_on');

        if ($source === null || $cutoff === null) {
            return null;
        }

        $nextSeason = new Season(['age_cutoff_on' => CarbonImmutable::instance($cutoff)]);
        $players = Player::rosteredIn($source)->orderBy('last_name')->orderBy('first_name')->orderBy('id')->get();

        $movingUp = $players
            ->map(fn (Player $player): array => [
                'name' => $player->fullName(),
                'from' => $suggestDivision->handle($player, $source),
                'to' => $suggestDivision->handle($player, $nextSeason),
            ])
            ->filter(fn (array $change): bool => $change['from']?->id !== $change['to']?->id)
            ->map(fn (array $change): array => [
                'name' => $change['name'],
                'from' => $change['from'] instanceof Division ? $change['from']->name : null,
                'to' => $change['to'] instanceof Division ? $change['to']->name : null,
            ])
            ->all();

        return [
            'returningCount' => $players->count(),
            'movingUp' => array_values($movingUp),
        ];
    }
}
