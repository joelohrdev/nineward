<?php

use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\User;

test('entering an organization starts a logged support session', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->post(route('platform.organizations.support-session.store', $organization), ['reason' => 'Owner asked for help'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('support_session.organization_id', $organization->id);

    $log = PlatformAuditLog::sole();
    expect($log->action)->toBe('support.started')
        ->and($log->organization_id)->toBe($organization->id)
        ->and($log->reason)->toBe('Owner asked for help');
});

test('entering an organization needs a reason', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->post(route('platform.organizations.support-session.store', $organization), ['reason' => ''])
        ->assertSessionHasErrors(['reason' => 'Say why you need to enter this organization.']);

    expect(PlatformAuditLog::count())->toBe(0);
});

test('leaving ends the support session and logs it', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->withSession(['support_session' => ['organization_id' => $organization->id]])
        ->delete(route('platform.support-session.destroy'))
        ->assertRedirect(route('platform.organizations.show', $organization))
        ->assertSessionMissing('support_session.organization_id');

    expect(PlatformAuditLog::sole()->action)->toBe('support.ended');
});

test('changes made during a support session are logged', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->platformAdmin()->memberOf(Organization::factory()->create())->create();

    $this->actingAs($admin)
        ->withSession(['support_session' => ['organization_id' => $organization->id]])
        ->put(route('current-organization.update'), ['organization_id' => $organization->id]);

    $log = PlatformAuditLog::sole();
    expect($log->action)->toBe('support.write')
        ->and($log->organization_id)->toBe($organization->id)
        ->and($log->metadata)->toMatchArray(['method' => 'PUT', 'route' => 'current-organization.update']);
});

test('reading pages during a support session is not logged as a change', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->withSession(['support_session' => ['organization_id' => $organization->id]])
        ->get(route('dashboard'))
        ->assertOk();

    expect(PlatformAuditLog::count())->toBe(0);
});
