<?php

namespace App\Support;

use App\Models\Season;
use Illuminate\Contracts\Session\Session;

/**
 * The season a user is looking at. Picking a season in the switcher opens
 * that season's teams and remembers it, so pages without a season in their
 * URL (home, players) keep showing it. Until a user picks one, it's the
 * organization's active season.
 */
class SelectedSeason
{
    public function __construct(
        private Session $session,
        private CurrentOrganization $currentOrganization,
    ) {}

    public function remember(Season $season): void
    {
        $this->session->put($this->key($season->organization_id), $season->id);
    }

    public function get(): ?Season
    {
        $organization = $this->currentOrganization->get();

        if ($organization === null) {
            return null;
        }

        $selectedId = $this->session->get($this->key($organization->id));

        // The tenant scope means a stale or foreign ID simply isn't found.
        $selected = is_int($selectedId) ? Season::find($selectedId) : null;

        return $selected ?? ($organization->active_season_id === null ? null : Season::find($organization->active_season_id));
    }

    private function key(int $organizationId): string
    {
        return "selected_season.{$organizationId}";
    }
}
