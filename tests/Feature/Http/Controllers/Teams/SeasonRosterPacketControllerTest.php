<?php

use App\Enums\OrganizationRole;
use App\Jobs\BuildSeasonRosterPacket;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Notifications\SeasonRosterPacketReady;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

test('asking for the season packet queues it', function () {
    Queue::fake();
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->post(route('seasons.roster-packet.store', $season))
        ->assertRedirect();

    Queue::assertPushed(BuildSeasonRosterPacket::class, fn (BuildSeasonRosterPacket $job) => $job->seasonId === $season->id
        && $job->organizationId === $organization->id
        && $job->requestedById === $organization->owner_id);
});

test('the packet is stored privately and the requester is emailed a link', function () {
    Storage::fake('local');
    Notification::fake();
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();
    foreach (['Red', 'Blue'] as $name) {
        rosterPlayer(Team::factory()->for($season)->create(['name' => $name, 'slug' => strtolower($name)]), Player::factory()->for($organization)->create());
    }

    (new BuildSeasonRosterPacket($organization->id, $season->id, $organization->owner_id))->handle(app(CurrentOrganization::class));

    Storage::disk('local')->assertExists(BuildSeasonRosterPacket::path($season));
    expect(Storage::disk('local')->get(BuildSeasonRosterPacket::path($season)))->toStartWith('%PDF');
    Notification::assertSentTo($organization->owner, SeasonRosterPacketReady::class);
});

test('the download link is signed and still needs someone who manages the organization', function () {
    Storage::fake('local');
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();
    Storage::disk('local')->put(BuildSeasonRosterPacket::path($season), '%PDF-fake');
    $signed = URL::temporarySignedRoute('seasons.roster-packet.show', now()->addHour(), ['season' => $season->id]);

    $this->actingAs($organization->owner)->get(route('seasons.roster-packet.show', $season))->assertForbidden();
    $this->actingAs($organization->owner)->get($signed)->assertOk()->assertDownload();

    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();
    $this->actingAs($coach)->get($signed)->assertForbidden();
});
