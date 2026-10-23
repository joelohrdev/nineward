<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\Season;
use App\Models\User;
use App\Support\BrandColor;
use App\Support\CurrentOrganization;
use App\Support\GuardianAccounts;
use App\Support\OrganizationRoleResolver;
use App\Support\SelectedSeason;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'currentOrganization' => fn (): ?array => $this->currentOrganization($request),
            'organizations' => fn (): array => $this->organizations($request),
            'supportSession' => fn (): ?array => $this->supportSession(),
            'seasons' => fn (): array => $this->seasons(),
            'selectedSeasonId' => fn (): ?int => app(SelectedSeason::class)->get()?->id,
            'hasClaimedPlayers' => fn (): bool => $request->user() instanceof User && app(GuardianAccounts::class)->hasClaimed($request->user()),
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string, primaryColor: string|null, brandTextColor: string|null, logoUrl: string|null, role: string|null, trialEndsAt: string|null, isOnTrial: bool}|null
     */
    private function currentOrganization(Request $request): ?array
    {
        $organization = app(CurrentOrganization::class)->get();
        $user = $request->user();

        if ($organization === null || ! $user instanceof User) {
            return null;
        }

        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'primaryColor' => $organization->primary_color,
            'brandTextColor' => $organization->primary_color === null ? null : (new BrandColor($organization->primary_color))->textColor(),
            'logoUrl' => $organization->logoUrl(),
            'role' => app(OrganizationRoleResolver::class)->for($user, $organization)?->value,
            'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
            'isOnTrial' => $organization->onGenericTrial() && ! $organization->subscribed(),
        ];
    }

    /**
     * The current organization's seasons, newest first, for the season switcher.
     *
     * @return list<array{id: int, name: string, isActive: bool, hasEnded: bool}>
     */
    private function seasons(): array
    {
        $organization = app(CurrentOrganization::class)->get();

        if ($organization === null) {
            return [];
        }

        return array_values(Season::query()
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Season $season): array => [
                'id' => $season->id,
                'name' => $season->name,
                'isActive' => $season->id === $organization->active_season_id,
                'hasEnded' => $season->hasEnded(),
            ])
            ->all());
    }

    /**
     * @return array{organizationName: string}|null
     */
    private function supportSession(): ?array
    {
        $currentOrganization = app(CurrentOrganization::class);

        if (! $currentOrganization->isSupportSession()) {
            return null;
        }

        return ['organizationName' => $currentOrganization->get()->name ?? ''];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function organizations(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return [];
        }

        $organizations = $user->organizations()
            ->orderBy('organizations.name')
            ->orderBy('organizations.id')
            ->get(['organizations.id', 'organizations.name'])
            ->map(fn (Organization $organization): array => [
                'id' => $organization->id,
                'name' => $organization->name,
            ]);

        return array_values($organizations->all());
    }
}
