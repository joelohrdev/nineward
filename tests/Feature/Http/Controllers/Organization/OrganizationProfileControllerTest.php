<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('updates the name, brand color and logo', function () {
    Storage::fake('public');
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.profile.update'), [
            'name' => 'Cary Trojans',
            'primary_color' => '#b5532c',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        ])
        ->assertRedirect(route('organization.profile.edit'));

    $organization->refresh();
    expect($organization->name)->toBe('Cary Trojans')
        ->and($organization->primary_color)->toBe('#B5532C');
    Storage::disk('public')->assertExists($organization->logo_path);
});

test('replacing the logo deletes the old file', function () {
    Storage::fake('public');
    $organization = Organization::factory()->create();
    $oldLogo = UploadedFile::fake()->image('old.png')->store('logos', 'public');
    $organization->forceFill(['logo_path' => $oldLogo])->save();

    $this->actingAs($organization->owner)->post(route('organization.profile.update'), [
        'name' => $organization->name,
        'logo' => UploadedFile::fake()->image('new.png'),
    ]);

    Storage::disk('public')->assertMissing($oldLogo);
});

test('removes the logo', function () {
    Storage::fake('public');
    $organization = Organization::factory()->create();
    $logo = UploadedFile::fake()->image('old.png')->store('logos', 'public');
    $organization->forceFill(['logo_path' => $logo])->save();

    $this->actingAs($organization->owner)->post(route('organization.profile.update'), [
        'name' => $organization->name,
        'remove_logo' => '1',
    ]);

    expect($organization->refresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($logo);
});

test('rejects files that are not images', function (UploadedFile $file) {
    Storage::fake('public');
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.profile.update'), ['name' => $organization->name, 'logo' => $file])
        ->assertSessionHasErrors('logo');

    expect($organization->refresh()->logo_path)->toBeNull();
})->with([
    'svg' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'pdf' => fn () => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
    'too large' => fn () => UploadedFile::fake()->image('logo.png')->size(3000),
]);

test('rejects a brand color that is hard to read and suggests one that works', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.profile.update'), ['name' => $organization->name, 'primary_color' => '#FFD700'])
        ->assertSessionHasErrors(['primary_color' => 'That color is hard to read on buttons and on dark pages. The closest color that works is #A38A00.']);
});

test('the brand color is shared with every page', function () {
    $organization = Organization::factory()->create(['primary_color' => '#B5532C']);

    $this->actingAs($organization->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentOrganization.primaryColor', '#B5532C')
            ->where('currentOrganization.brandTextColor', '#FFFFFF'));
});

test('coaches cannot change the profile', function () {
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

    $this->actingAs($coach)
        ->post(route('organization.profile.update'), ['name' => 'Hijacked'])
        ->assertForbidden();

    expect($organization->refresh()->name)->not->toBe('Hijacked');
});
