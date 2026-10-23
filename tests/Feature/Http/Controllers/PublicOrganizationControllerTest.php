<?php

use App\Actions\Organizations\CreateOrganization;
use App\Models\Form;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function organizationWithActiveTeam(bool $showRosters): array
{
    $organization = Organization::factory()->create(['show_public_rosters' => $showRosters]);
    $season = Season::factory()->for($organization)->create(['name' => 'Spring 2027']);
    $organization->forceFill(['active_season_id' => $season->id])->save();
    $team = Team::factory()->for($season)->create(['name' => '10U Red', 'slug' => '10u-red']);
    rosterPlayer($team, Player::factory()->for($organization)->create(['first_name' => 'Caleb', 'last_name' => 'Ortiz', 'date_of_birth' => '2016-03-14']), ['jersey_number' => 7]);

    return [$organization, $team];
}

test('the public page lists open registration forms and this season\'s teams', function () {
    [$organization] = organizationWithActiveTeam(showRosters: false);
    publishedRegistrationForm($organization, formAttributes: ['title' => 'Tryouts']);
    Form::factory()->for($organization)->create(['title' => 'Draft form']);

    $this->get(route('organizations.public.show', $organization->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/organization')
            ->has('forms', 1)
            ->where('forms.0.title', 'Tryouts')
            ->where('teams.0.name', '10U Red')
            ->where('showRosters', false));
});

test('public rosters are off unless the organization turns them on', function () {
    [$organization, $team] = organizationWithActiveTeam(showRosters: false);

    $this->get(route('organizations.public.team', [$organization->slug, $team->slug]))->assertNotFound();
});

test('a public roster shows jersey numbers, first names and last initials only', function () {
    [$organization, $team] = organizationWithActiveTeam(showRosters: true);

    $response = $this->get(route('organizations.public.team', [$organization->slug, $team->slug]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('public/team')
        ->where('players', [['jerseyNumber' => 7, 'name' => 'Caleb O.']]));
    expect($response->getContent())->not->toContain('Ortiz')->not->toContain('2016');
});

test('the app\'s own pages still work alongside public organization pages', function () {
    $this->get(route('login'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/login'));
    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->get('/no-such-organization')->assertNotFound();
});

test('a new organization never takes a slug the app already uses', function (string $name) {
    $organization = app(CreateOrganization::class)->handle(User::factory()->create(), $name);

    expect($organization->slug)->toBe(Str::slug($name).'-2');
})->with(['Dashboard', 'Login', 'Players', 'Settings']);

test('turning public rosters on is part of the profile', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.profile.update'), ['name' => $organization->name, 'show_public_rosters' => '1'])
        ->assertSessionHasNoErrors();

    expect($organization->refresh()->show_public_rosters)->toBeTrue();
});
