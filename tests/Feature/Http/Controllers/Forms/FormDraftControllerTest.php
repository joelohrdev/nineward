<?php

use App\Enums\OrganizationRole;
use App\Forms\FormSchema;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Organization;
use App\Models\User;

/**
 * The starter questions plus any extra fields, as the builder would send them.
 *
 * @param  list<array<string, mixed>>  $extra
 * @return list<array<string, mixed>>
 */
function draftFields(array $extra = []): array
{
    return [...FormSchema::starter()['fields'], ...$extra];
}

function customField(array $overrides = []): array
{
    return [
        'id' => 'f_custom01',
        'type' => 'short_text',
        'label' => 'Allergies?',
        'help' => null,
        'required' => false,
        'options' => [],
        'maps_to' => null,
        ...$overrides,
    ];
}

test('saves the questions to the draft', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->put(route('forms.draft.update', $form), ['fields' => draftFields([customField()])])
        ->assertSessionHasNoErrors();

    $draft = FormVersion::where('form_id', $form->id)->sole();
    expect($draft->schema['fields'])->toHaveCount(7)
        ->and($draft->schema['fields'][6]['label'])->toBe('Allergies?')
        ->and($draft->published_at)->toBeNull();
});

test('mapped questions take their type and choices from the mapping, whatever the builder sends', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();

    $this->actingAs($organization->owner)->put(route('forms.draft.update', $form), ['fields' => draftFields([
        customField(['type' => 'long_text', 'label' => 'Bats', 'maps_to' => 'player.bats', 'options' => ['Hacked']]),
    ])]);

    $bats = collect(FormVersion::where('form_id', $form->id)->sole()->schema['fields'])->firstWhere('maps_to', 'player.bats');
    expect($bats['type'])->toBe('select')
        ->and($bats['options'])->toBe(['Right', 'Left', 'Switch']);
});

test('core questions are always required', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();
    $fields = draftFields();
    $fields[0]['required'] = false;

    $this->actingAs($organization->owner)->put(route('forms.draft.update', $form), ['fields' => $fields]);

    expect(FormVersion::where('form_id', $form->id)->sole()->schema['fields'][0]['required'])->toBeTrue();
});

test('rejects a form missing a core question', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();
    $fields = collect(draftFields())->reject(fn ($field) => $field['maps_to'] === 'guardian.email')->values()->all();

    $this->actingAs($organization->owner)
        ->put(route('forms.draft.update', $form), ['fields' => $fields])
        ->assertSessionHasErrors(['fields' => "Every registration form needs “Parent or guardian's email”."]);
});

test('rejects invalid schemas', function (array $field, string $errorKey) {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->put(route('forms.draft.update', $form), ['fields' => draftFields([customField($field)])])
        ->assertSessionHasErrors($errorKey);
})->with([
    'unknown type' => [['type' => 'file_upload'], 'fields.6.type'],
    'unknown mapping' => [['maps_to' => 'player.ssn'], 'fields.6.maps_to'],
    'missing label' => [['label' => ''], 'fields.6.label'],
    'malformed id' => [['id' => 'not-an-id'], 'fields.6.id'],
    'dropdown with one choice' => [['type' => 'select', 'options' => ['Only']], 'fields.6.options'],
    'dropdown with a repeated choice' => [['type' => 'select', 'options' => ['YS', 'ys']], 'fields.6.options'],
]);

test('rejects two questions with the same id', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->put(route('forms.draft.update', $form), ['fields' => draftFields([customField(), customField(['label' => 'Again'])])])
        ->assertSessionHasErrors('fields.6.id');
});

test('rejects the same player detail asked twice', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->put(route('forms.draft.update', $form), ['fields' => draftFields([
            customField(['maps_to' => 'player.bats']),
            customField(['id' => 'f_custom02', 'maps_to' => 'player.bats']),
        ])])
        ->assertSessionHasErrors('fields');
});

test('editing a published form starts a new draft and leaves the published version alone', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->published()->create();
    $published = FormVersion::where('form_id', $form->id)->sole();
    $originalSchema = $published->schema;

    $this->actingAs($organization->owner)
        ->put(route('forms.draft.update', $form), ['fields' => draftFields([customField()])])
        ->assertSessionHasNoErrors();

    expect($published->refresh()->schema)->toBe($originalSchema)
        ->and(FormVersion::where('form_id', $form->id)->count())->toBe(2)
        ->and(FormVersion::where('form_id', $form->id)->whereNull('published_at')->sole()->version)->toBe(2)
        ->and($form->refresh()->current_version_id)->toBe($published->id);
});

test('coaches cannot edit forms', function () {
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

    $this->actingAs($coach)
        ->put(route('forms.draft.update', Form::factory()->for($organization)->create()), ['fields' => draftFields()])
        ->assertForbidden();
});
