<?php

use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;

test('adds a guardian to a player, reusing an existing one with that email', function () {
    $organization = Organization::factory()->create();
    $player = Player::factory()->for($organization)->create();
    $existing = Guardian::factory()->for($organization)->create(['email' => 'dad@example.com']);

    $this->actingAs($organization->owner)
        ->post(route('players.guardians.store', $player), [
            'first_name' => 'Sam',
            'last_name' => 'Ortiz',
            'email' => 'DAD@example.com',
            'relationship' => 'Father',
        ])
        ->assertRedirect(route('players.show', $player));

    $linked = $player->guardians()->withoutGlobalScopes()->sole();
    expect($linked->id)->toBe($existing->id)
        ->and($linked->pivot->relationship)->toBe('Father');
});

test('unlinks a guardian without deleting them', function () {
    $organization = Organization::factory()->create();
    $player = Player::factory()->for($organization)->create();
    $guardian = Guardian::factory()->for($organization)->create();
    $player->guardians()->attach($guardian);

    $this->actingAs($organization->owner)
        ->delete(route('players.guardians.destroy', [$player, $guardian]))
        ->assertRedirect(route('players.show', $player));

    expect($player->guardians()->withoutGlobalScopes()->count())->toBe(0)
        ->and(Guardian::withoutGlobalScopes()->count())->toBe(1);
});
