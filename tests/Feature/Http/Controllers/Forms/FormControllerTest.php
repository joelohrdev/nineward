<?php

use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Organization;
use App\Models\Season;
use Inertia\Testing\AssertableInertia as Assert;

test('creates a draft form with the core questions and opens the builder', function () {
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();

    $response = $this->actingAs($organization->owner)
        ->post(route('forms.store'), ['title' => 'Spring 2027 Tryouts', 'season_id' => $season->id]);

    $form = Form::withoutGlobalScopes()->sole();
    $response->assertRedirect(route('forms.edit', $form));
    expect($form->slug)->toBe('spring-2027-tryouts')
        ->and($form->season_id)->toBe($season->id)
        ->and(collect(FormVersion::sole()->schema['fields'])->pluck('maps_to')->all())
        ->toBe(['player.first_name', 'player.last_name', 'player.date_of_birth', 'guardian.first_name', 'guardian.last_name', 'guardian.email']);
});

test('the builder opens the published questions when there is no draft', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->published()->create();

    $this->actingAs($organization->owner)
        ->get(route('forms.edit', $form))
        ->assertInertia(fn (Assert $page) => $page
            ->component('forms/edit')
            ->has('fields', 6)
            ->where('form.publishedVersion', 1)
            ->where('form.hasUnpublishedChanges', false));
});

test('lists forms with their status', function () {
    $organization = Organization::factory()->create();
    Form::factory()->for($organization)->published()->create(['title' => 'Open one']);
    Form::factory()->create();

    $this->actingAs($organization->owner)
        ->get(route('forms.index'))
        ->assertInertia(fn (Assert $page) => $page->has('forms', 1)->where('forms.0.statusLabel', 'Open'));
});

test('a published form cannot be deleted', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->published()->create();

    $this->actingAs($organization->owner)
        ->delete(route('forms.destroy', $form))
        ->assertSessionHasErrors(['form' => 'Families have seen this form, so it can only be closed, not deleted.']);

    expect(Form::withoutGlobalScopes()->count())->toBe(1);
});

test('a never-published form can be deleted', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();

    $this->actingAs($organization->owner)->delete(route('forms.destroy', $form))->assertRedirect(route('forms.index'));

    expect(Form::withoutGlobalScopes()->count())->toBe(0);
});
