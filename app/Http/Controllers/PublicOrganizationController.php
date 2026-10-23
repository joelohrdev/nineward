<?php

namespace App\Http\Controllers;

use App\Enums\FormStatus;
use App\Models\Form;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The organization's public home page. It only shows what the organization
 * chose to make public: never birth dates or contact details.
 */
class PublicOrganizationController extends Controller
{
    public function show(Organization $organization): Response
    {
        $season = $organization->active_season_id === null ? null : Season::find($organization->active_season_id);

        $forms = Form::query()
            ->with('season:id,name,is_registration_open')
            ->where('status', FormStatus::Published)
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->filter(fn (Form $form): bool => ($form->season === null || $form->season->is_registration_open)
                && (! $form->chargesFee() || $organization->canAcceptPayments()))
            ->values();

        $teams = $season === null ? collect() : $season->teams()->with('division:id,name,display_order')->get()
            ->sortBy([
                fn (Team $a, Team $b) => $a->division->display_order <=> $b->division->display_order,
                fn (Team $a, Team $b) => strcmp($a->name, $b->name),
            ])
            ->values();

        return Inertia::render('public/organization', [
            'organization' => $this->summary($organization),
            'seasonName' => $season?->name,
            'forms' => $forms->map(fn (Form $form): array => [
                'title' => $form->title,
                'slug' => $form->slug,
                'seasonName' => $form->season?->name,
                'priceCents' => $form->price_cents,
            ])->all(),
            'teams' => $teams->map(fn (Team $team): array => [
                'name' => $team->name,
                'slug' => $team->slug,
                'divisionName' => $team->division->name,
            ])->all(),
            'showRosters' => $organization->show_public_rosters,
        ]);
    }

    /**
     * A public roster for the active season: jersey numbers, first names and
     * last initials only, and only if the organization turned this on.
     */
    public function team(Organization $organization, string $team): Response
    {
        abort_unless($organization->show_public_rosters && $organization->active_season_id !== null, 404);

        $record = Team::query()
            ->where('season_id', $organization->active_season_id)
            ->where('slug', $team)
            ->with(['division:id,name', 'season:id,name'])
            ->firstOrFail();

        $players = $record->players()
            ->orderByRaw('team_player.jersey_number is null')
            ->orderBy('team_player.jersey_number')
            ->orderBy('players.first_name')
            ->orderBy('players.id')
            ->get(['players.id', 'players.first_name', 'players.last_name']);

        return Inertia::render('public/team', [
            'organization' => $this->summary($organization),
            'team' => [
                'name' => $record->name,
                'divisionName' => $record->division->name,
                'seasonName' => $record->season->name,
            ],
            'players' => $players->map(fn (Player $player): array => [
                'jerseyNumber' => $player->rosterEntry?->jersey_number,
                'name' => $player->first_name.' '.mb_substr($player->last_name, 0, 1).'.',
            ])->all(),
        ]);
    }

    /**
     * @return array{name: string, slug: string, color: string|null, logoUrl: string|null}
     */
    private function summary(Organization $organization): array
    {
        return [
            'name' => $organization->name,
            'slug' => $organization->slug,
            'color' => $organization->primary_color,
            'logoUrl' => $organization->logoUrl(),
        ];
    }
}
