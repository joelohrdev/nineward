<?php

use App\Models\Guardian;
use App\Models\Organization;
use App\Models\User;

test('corrects a guardian\'s contact details', function () {
    $organization = Organization::factory()->create();
    $guardian = Guardian::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->put(route('guardians.update', $guardian), [
            'first_name' => 'Maria',
            'last_name' => 'Ortiz',
            'email' => 'maria.new@example.com',
            'phone' => '919-555-0199',
        ])
        ->assertSessionHasNoErrors();

    $guardian = Guardian::withoutGlobalScopes()->find($guardian->id);
    expect($guardian->email)->toBe('maria.new@example.com')
        ->and($guardian->phone)->toBe('919-555-0199');
});

test('rejects an email another guardian already uses', function () {
    $organization = Organization::factory()->create();
    Guardian::factory()->for($organization)->create(['email' => 'taken@example.com']);
    $guardian = Guardian::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->put(route('guardians.update', $guardian), ['first_name' => 'A', 'last_name' => 'B', 'email' => 'taken@example.com'])
        ->assertSessionHasErrors(['email' => 'Another guardian already uses that email.']);
});

test('a claimed guardian\'s email cannot be changed', function () {
    $organization = Organization::factory()->create();
    $guardian = Guardian::factory()->for($organization)->claimedBy(User::factory()->create(['email' => 'parent@example.com']))->create();

    $this->actingAs($organization->owner)
        ->put(route('guardians.update', $guardian), ['first_name' => 'A', 'last_name' => 'B', 'email' => 'other@example.com'])
        ->assertSessionHasErrors(['email' => "This guardian has claimed their account, so their email can't be changed here."]);
});
