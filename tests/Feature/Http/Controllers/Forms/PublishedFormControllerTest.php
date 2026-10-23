<?php

use App\Enums\FormStatus;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Organization;

test('publishing freezes the draft and opens the form', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->post(route('forms.publication.store', $form))
        ->assertRedirect();

    $form->refresh();
    $version = FormVersion::where('form_id', $form->id)->sole();
    expect($form->status)->toBe(FormStatus::Published)
        ->and($form->current_version_id)->toBe($version->id)
        ->and($version->published_at)->not->toBeNull();
});

test('publishing changes switches families to the new version', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->published()->create();
    $draft = $form->versions()->create(['version' => 2, 'schema' => $form->versions()->first()->schema]);

    $this->actingAs($organization->owner)->post(route('forms.publication.store', $form));

    expect($form->refresh()->current_version_id)->toBe($draft->id)
        ->and(FormVersion::whereNotNull('published_at')->count())->toBe(2);
});

test('closing stops registrations and reopening keeps the same version', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->published()->create();
    $version = $form->current_version_id;

    $this->actingAs($organization->owner)->delete(route('forms.publication.destroy', $form));
    expect($form->refresh()->status)->toBe(FormStatus::Closed);

    $this->actingAs($organization->owner)->post(route('forms.publication.store', $form));
    expect($form->refresh()->status)->toBe(FormStatus::Published)
        ->and($form->current_version_id)->toBe($version);
});
