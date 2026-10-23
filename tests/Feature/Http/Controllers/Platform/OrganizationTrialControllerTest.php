<?php

use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\User;

test('extends a running trial from its current end date and logs why', function () {
    $this->freezeSecond();
    $organization = Organization::factory()->create(['trial_ends_at' => now()->addDays(3)]);
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->put(route('platform.organizations.trial.update', $organization), ['days' => 10, 'reason' => 'Season started late'])
        ->assertRedirect();

    expect($organization->refresh()->trial_ends_at->equalTo(now()->addDays(13)))->toBeTrue();

    $log = PlatformAuditLog::sole();
    expect($log->action)->toBe('trial.extended')
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->organization_id)->toBe($organization->id)
        ->and($log->reason)->toBe('Season started late');
});

test('extends an ended trial from today', function () {
    $this->freezeSecond();
    $organization = Organization::factory()->trialExpired()->create();

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->put(route('platform.organizations.trial.update', $organization), ['days' => 7, 'reason' => 'Goodwill']);

    expect($organization->refresh()->trial_ends_at->equalTo(now()->addDays(7)))->toBeTrue();
});

test('an extension needs a reason and a length of 1 to 90 days', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->put(route('platform.organizations.trial.update', $organization), ['days' => 91])
        ->assertSessionHasErrors([
            'days' => 'The days field must not be greater than 90.',
            'reason' => 'The reason field is required.',
        ]);

    expect(PlatformAuditLog::count())->toBe(0);
});
