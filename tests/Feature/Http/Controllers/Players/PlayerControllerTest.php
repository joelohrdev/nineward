<?php

use App\Enums\OrganizationRole;
use App\Models\Division;
use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function validPlayer(array $overrides = []): array
{
    return [
        'first_name' => 'Caleb',
        'last_name' => 'Ortiz',
        'date_of_birth' => '2016-03-14',
        'bats' => 'R',
        'throws' => 'R',
        'primary_position' => 'C',
        'guardians' => [
            ['first_name' => 'Maria', 'last_name' => 'Ortiz', 'email' => 'maria@example.com', 'phone' => '919-555-0100', 'relationship' => 'Mother'],
        ],
        ...$overrides,
    ];
}

describe('index', function () {
    test('lists players alphabetically with league age and suggested division for the active season', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create(['name' => 'Spring 2026', 'age_cutoff_on' => '2026-05-01']);
        $organization->forceFill(['active_season_id' => $season->id])->save();
        Division::factory()->for($organization)->create(['name' => '10U', 'max_age' => 10]);
        Player::factory()->for($organization)->create(['first_name' => 'Zed', 'last_name' => 'Adams', 'date_of_birth' => '2016-01-01']);
        Player::factory()->for($organization)->create(['first_name' => 'Amy', 'last_name' => 'Brown']);
        Player::factory()->create(['last_name' => 'Elsewhere']);

        $this->actingAs($organization->owner)
            ->get(route('players.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('players/index')
                ->has('players.data', 2)
                ->where('players.data.0.name', 'Zed Adams')
                ->where('players.data.0.leagueAge', 10)
                ->where('players.data.0.suggestedDivision', '10U')
                ->where('activeSeasonName', 'Spring 2026')
                ->missing('counts')
                ->loadDeferredProps(fn (Assert $reload) => $reload->where('counts.players', 2)));
    });

    test('searches by player name and by guardian email', function () {
        $organization = Organization::factory()->create();
        $caleb = Player::factory()->for($organization)->create(['first_name' => 'Caleb', 'last_name' => 'Ortiz']);
        $owen = Player::factory()->for($organization)->create(['first_name' => 'Owen', 'last_name' => 'Park']);
        $owen->guardians()->attach(Guardian::factory()->for($organization)->create(['email' => 'jpark@example.com']));

        $this->actingAs($organization->owner)
            ->get(route('players.index', ['search' => 'caleb ort']))
            ->assertInertia(fn (Assert $page) => $page->has('players.data', 1)->where('players.data.0.id', $caleb->id));

        $this->actingAs($organization->owner)
            ->get(route('players.index', ['search' => 'jpark@']))
            ->assertInertia(fn (Assert $page) => $page->has('players.data', 1)->where('players.data.0.id', $owen->id));
    });

    test('shows only removed players when asked', function () {
        $organization = Organization::factory()->create();
        Player::factory()->for($organization)->create();
        $removed = Player::factory()->for($organization)->create();
        $removed->delete();

        $this->actingAs($organization->owner)
            ->get(route('players.index', ['removed' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('players.data', 1)->where('players.data.0.id', $removed->id));
    });

    test('coaches cannot see the master list', function () {
        $coach = User::factory()->memberOf(Organization::factory()->create(), OrganizationRole::Coach)->create();

        $this->actingAs($coach)->get(route('players.index'))->assertForbidden();
    });
});

describe('store', function () {
    test('adds a player with their guardian', function () {
        $organization = Organization::factory()->create();

        $response = $this->actingAs($organization->owner)->post(route('players.store'), validPlayer());

        $player = Player::withoutGlobalScopes()->sole();
        $response->assertRedirect(route('players.show', $player));
        expect($player->organization_id)->toBe($organization->id)
            ->and($player->fullName())->toBe('Caleb Ortiz');

        $guardian = Guardian::withoutGlobalScopes()->sole();
        expect($guardian->email)->toBe('maria@example.com')
            ->and($guardian->organization_id)->toBe($organization->id)
            ->and($player->guardians()->withoutGlobalScopes()->first()->pivot->relationship)->toBe('Mother');
    });

    test('a sibling reuses the existing guardian', function () {
        $organization = Organization::factory()->create();
        $guardian = Guardian::factory()->for($organization)->create(['email' => 'maria@example.com']);

        $this->actingAs($organization->owner)->post(route('players.store'), validPlayer([
            'guardians' => [['first_name' => 'Maria', 'last_name' => 'Ortiz', 'email' => 'Maria@Example.com']],
        ]));

        expect(Guardian::withoutGlobalScopes()->count())->toBe(1)
            ->and(Player::withoutGlobalScopes()->sole()->guardians()->withoutGlobalScopes()->first()->id)->toBe($guardian->id);
    });

    test('requires a guardian', function () {
        $organization = Organization::factory()->create();

        $this->actingAs($organization->owner)
            ->post(route('players.store'), validPlayer(['guardians' => []]))
            ->assertSessionHasErrors(['guardians' => 'Add at least one parent or guardian.']);

        expect(Player::withoutGlobalScopes()->count())->toBe(0);
    });

    test('requires the player and guardian basics', function () {
        $organization = Organization::factory()->create();

        $this->actingAs($organization->owner)
            ->post(route('players.store'), ['guardians' => [['first_name' => '']]])
            ->assertSessionHasErrors([
                'first_name' => 'The first name field is required.',
                'last_name' => 'The last name field is required.',
                'date_of_birth' => 'The date of birth field is required.',
                'guardians.0.first_name' => 'The guardian first name field is required.',
                'guardians.0.email' => 'The guardian email field is required.',
            ]);
    });

    test('rejects a birth date in the future', function () {
        $organization = Organization::factory()->create();

        $this->actingAs($organization->owner)
            ->post(route('players.store'), validPlayer(['date_of_birth' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['date_of_birth' => 'The date of birth has to be in the past.']);
    });

    test('rejects two guardians with the same email', function () {
        $organization = Organization::factory()->create();

        $this->actingAs($organization->owner)
            ->post(route('players.store'), validPlayer(['guardians' => [
                ['first_name' => 'A', 'last_name' => 'B', 'email' => 'same@example.com'],
                ['first_name' => 'C', 'last_name' => 'D', 'email' => 'SAME@example.com'],
            ]]))
            ->assertSessionHasErrors(['guardians.0.email' => 'Each guardian needs their own email address.']);
    });
});

describe('show', function () {
    test('shows the player with their guardians', function () {
        $organization = Organization::factory()->create();
        $player = Player::factory()->for($organization)->create();
        $player->guardians()->attach(Guardian::factory()->for($organization)->create(), ['relationship' => 'Father']);

        $this->actingAs($organization->owner)
            ->get(route('players.show', $player))
            ->assertInertia(fn (Assert $page) => $page
                ->component('players/show')
                ->where('player.id', $player->id)
                ->has('guardians', 1)
                ->where('guardians.0.relationship', 'Father')
                ->where('guardians.0.isClaimed', false));
    });
});

describe('update', function () {
    test('updates the player without touching guardians', function () {
        $organization = Organization::factory()->create();
        $player = Player::factory()->for($organization)->create();
        $player->guardians()->attach(Guardian::factory()->for($organization)->create());

        $this->actingAs($organization->owner)
            ->put(route('players.update', $player), [
                'first_name' => 'Calvin',
                'last_name' => $player->last_name,
                'date_of_birth' => '2015-07-04',
                'bats' => 'S',
            ])
            ->assertRedirect(route('players.show', $player));

        $player = Player::withoutGlobalScopes()->find($player->id);
        expect($player->first_name)->toBe('Calvin')
            ->and($player->bats->value)->toBe('S')
            ->and($player->guardians()->withoutGlobalScopes()->count())->toBe(1);
    });
});

describe('removal', function () {
    test('removing a player keeps them restorable', function () {
        $organization = Organization::factory()->create();
        $player = Player::factory()->for($organization)->create();

        $this->actingAs($organization->owner)
            ->delete(route('players.destroy', $player))
            ->assertRedirect(route('players.index'));
        expect(Player::withoutGlobalScopes()->onlyTrashed()->count())->toBe(1);

        $this->actingAs($organization->owner)
            ->post(route('players.restoration.store', $player))
            ->assertRedirect(route('players.show', $player));
        expect(Player::withoutGlobalScopes()->find($player->id)->trashed())->toBeFalse();
    });
});
