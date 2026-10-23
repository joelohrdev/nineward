<?php

use App\Models\OrganizationInvitation;

test('invitations that expired or were accepted over 30 days ago are pruned', function () {
    $current = OrganizationInvitation::factory()->create();
    $recentlyExpired = OrganizationInvitation::factory()->create(['expires_at' => now()->subDays(5)]);
    OrganizationInvitation::factory()->create(['expires_at' => now()->subDays(31)]);
    OrganizationInvitation::factory()->create(['accepted_at' => now()->subDays(31)]);

    $this->artisan('model:prune', ['--model' => [OrganizationInvitation::class]])->assertSuccessful();

    expect(OrganizationInvitation::pluck('id')->sort()->values()->all())->toBe([$current->id, $recentlyExpired->id]);
});
