<?php

use App\Actions\Divisions\SuggestDivision;
use App\Models\Division;
use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\DB;

function divisionsFor(Organization $organization): void
{
    Division::factory()->for($organization)->create(['name' => 'Varsity', 'min_age' => 15, 'max_age' => null]);
    Division::factory()->for($organization)->create(['name' => '10U', 'max_age' => 10]);
    Division::factory()->for($organization)->create(['name' => '12U', 'min_age' => 11, 'max_age' => 12]);
    Division::factory()->for($organization)->create(['name' => '9U', 'max_age' => 9]);
}

test('suggests the youngest division the age fits', function (int $age, ?string $division) {
    $organization = Organization::factory()->create();
    divisionsFor($organization);
    app(CurrentOrganization::class)->set($organization);

    expect(app(SuggestDivision::class)->forAge($age)?->name)->toBe($division);
})->with([
    '9 goes to 9U' => [9, '9U'],
    '10 goes to 10U' => [10, '10U'],
    '12 goes to 12U' => [12, '12U'],
    '13 fits nothing' => [13, null],
    '16 goes to the open Varsity' => [16, 'Varsity'],
]);

test('loads the divisions once no matter how many players it suggests for', function () {
    $organization = Organization::factory()->create();
    divisionsFor($organization);
    app(CurrentOrganization::class)->set($organization);
    $suggestDivision = app(SuggestDivision::class);

    DB::enableQueryLog();
    foreach ([8, 9, 10, 11, 12] as $age) {
        $suggestDivision->forAge($age);
    }

    expect(DB::getQueryLog())->toHaveCount(1);
});
