<?php

use App\Contracts\RegistrationPaymentGateway;
use App\Enums\FormStatus;
use App\Forms\FormSchema;
use App\Models\Form;
use App\Models\Organization;
use App\Models\Player;
use App\Models\RosterEntry;
use App\Models\Season;
use App\Models\StripeConnectAccount;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\FakeRegistrationPaymentGateway;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Put a player on a team's roster for the team's season.
 *
 * @param  array<string, mixed>  $attributes
 */
function rosterPlayer(Team $team, Player $player, array $attributes = []): RosterEntry
{
    $team->players()->attach($player, ['season_id' => $team->season_id, ...$attributes]);

    return RosterEntry::query()->where('team_id', $team->id)->where('player_id', $player->id)->sole();
}

/**
 * A team plus a player of the same organization, ready to roster.
 *
 * @return array{0: Team, 1: Player}
 */
function teamAndPlayer(?Organization $organization = null): array
{
    $organization ??= Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();

    return [
        Team::factory()->for($season)->create(),
        Player::factory()->for($organization)->create(),
    ];
}

/**
 * A published registration form with known question IDs: the six core
 * questions plus any extra fields given.
 *
 * @param  list<array<string, mixed>>  $extraFields
 */
function publishedRegistrationForm(Organization $organization, array $extraFields = [], array $formAttributes = []): Form
{
    $core = [
        ['id' => 'f_pfirst', 'maps_to' => 'player.first_name', 'type' => 'short_text', 'label' => "Player's first name"],
        ['id' => 'f_plast', 'maps_to' => 'player.last_name', 'type' => 'short_text', 'label' => "Player's last name"],
        ['id' => 'f_pdob', 'maps_to' => 'player.date_of_birth', 'type' => 'date', 'label' => "Player's date of birth"],
        ['id' => 'f_gfirst', 'maps_to' => 'guardian.first_name', 'type' => 'short_text', 'label' => "Guardian's first name"],
        ['id' => 'f_glast', 'maps_to' => 'guardian.last_name', 'type' => 'short_text', 'label' => "Guardian's last name"],
        ['id' => 'f_gemail', 'maps_to' => 'guardian.email', 'type' => 'email', 'label' => "Guardian's email"],
    ];

    $fields = array_map(fn (array $field): array => [
        'help' => null,
        'required' => true,
        'options' => [],
        ...$field,
    ], $core);

    $extra = array_map(fn (array $field): array => [
        'help' => null,
        'required' => false,
        'options' => [],
        'maps_to' => null,
        ...$field,
    ], $extraFields);

    $form = Form::factory()->for($organization)->create($formAttributes);
    $form->versions()->update(['schema' => FormSchema::normalize([...$fields, ...$extra])]);
    $version = $form->versions()->sole();
    $version->forceFill(['published_at' => now()])->save();
    $form->forceFill(['current_version_id' => $version->id, 'status' => FormStatus::Published])->save();

    return $form->refresh();
}

/**
 * Answers to the core questions of publishedRegistrationForm().
 *
 * @param  array<string, string|null>  $overrides
 * @return array<string, string|null>
 */
function registrationAnswers(array $overrides = []): array
{
    return [
        'f_pfirst' => 'Caleb',
        'f_plast' => 'Ortiz',
        'f_pdob' => '2016-03-14',
        'f_gfirst' => 'Maria',
        'f_glast' => 'Ortiz',
        'f_gemail' => 'maria@example.com',
        ...$overrides,
    ];
}

/**
 * Swap Stripe for a fake that records what the app asks of it.
 */
function fakePaymentGateway(): FakeRegistrationPaymentGateway
{
    $gateway = new FakeRegistrationPaymentGateway;
    app()->instance(RegistrationPaymentGateway::class, $gateway);

    return $gateway;
}

/**
 * Give an organization a Stripe account that can take payments.
 *
 * @param  array<string, mixed>  $attributes
 */
function connectStripe(Organization $organization, array $attributes = []): StripeConnectAccount
{
    $account = new StripeConnectAccount;
    $account->forceFill([
        'organization_id' => $organization->id,
        'stripe_account_id' => 'acct_'.$organization->id.'test',
        'charges_enabled' => true,
        'payouts_enabled' => true,
        'details_submitted' => true,
        ...$attributes,
    ])->save();

    return $account;
}
