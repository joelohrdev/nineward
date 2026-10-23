<?php

use App\Http\Controllers\BillingCheckoutController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BillingPortalController;
use App\Http\Controllers\CurrentOrganizationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Forms;
use App\Http\Controllers\GuardianClaimController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MyPlayersController;
use App\Http\Controllers\Organization;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\Platform;
use App\Http\Controllers\Players;
use App\Http\Controllers\PublicOrganizationController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\RegistrationPaymentController;
use App\Http\Controllers\StripeConnectWebhookController;
use App\Http\Controllers\Submissions;
use App\Http\Controllers\Teams;
use App\Http\Middleware\RecordSupportSessionWrites;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('stripe/connect-webhook', StripeConnectWebhookController::class)->name('stripe.connect-webhook');

Route::get('invitations/{invitation}', [InvitationController::class, 'show'])
    ->middleware(['signed', 'organization'])
    ->name('invitations.show');
Route::post('invitations/{invitation}/acceptance', [InvitationController::class, 'accept'])
    ->middleware(['signed', 'auth', 'verified'])
    ->name('invitations.acceptance.store');

Route::get('guardian-claims/{guardian}', [GuardianClaimController::class, 'show'])
    ->whereNumber('guardian')
    ->middleware(['signed', 'organization'])
    ->name('guardian-claims.show');
Route::post('guardian-claims/{guardian}', [GuardianClaimController::class, 'store'])
    ->whereNumber('guardian')
    ->middleware(['signed', 'auth', 'verified'])
    ->name('guardian-claims.store');

Route::middleware(['auth', 'verified', 'organization', RecordSupportSessionWrites::class])->group(function () {
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');

    Route::put('current-organization', [CurrentOrganizationController::class, 'update'])
        ->name('current-organization.update');

    Route::get('my-players', [MyPlayersController::class, 'index'])->name('my-players.index');

    Route::middleware('has-organization')->group(function () {
        Route::get('billing', [BillingController::class, 'show'])->name('billing.show');
        Route::post('billing/checkout', [BillingCheckoutController::class, 'store'])->name('billing.checkout.store');
        Route::post('billing/portal', [BillingPortalController::class, 'store'])->name('billing.portal.store');

        Route::middleware('has-access')->group(function () {
            Route::get('dashboard', DashboardController::class)->name('dashboard');

            Route::resource('seasons.teams', Teams\TeamController::class)
                ->only(['index', 'store', 'show', 'update', 'destroy'])
                ->scoped(['season' => 'id', 'team' => 'slug']);
            Route::post('teams/{team}/roster-entries', [Teams\RosterEntryController::class, 'store'])->name('teams.roster-entries.store');
            Route::put('teams/{team}/roster-entries/{rosterEntry}', [Teams\RosterEntryController::class, 'update'])
                ->scopeBindings()
                ->name('teams.roster-entries.update');
            Route::delete('teams/{team}/roster-entries/{rosterEntry}', [Teams\RosterEntryController::class, 'destroy'])
                ->scopeBindings()
                ->name('teams.roster-entries.destroy');
            Route::post('teams/{team}/roster-entries/{rosterEntry}/transfer', [Teams\RosterTransferController::class, 'store'])
                ->scopeBindings()
                ->name('teams.roster-entries.transfer.store');
            Route::get('teams/{team}/roster.csv', [Teams\TeamRosterExportController::class, 'show'])->name('teams.roster-export.show');
            Route::get('teams/{team}/roster.pdf', [Teams\TeamRosterPdfController::class, 'show'])->name('teams.roster-pdf.show');
            Route::post('seasons/{season}/roster-packet', [Teams\SeasonRosterPacketController::class, 'store'])->name('seasons.roster-packet.store');
            Route::get('seasons/{season}/roster-packet', [Teams\SeasonRosterPacketController::class, 'show'])
                ->middleware('signed')
                ->name('seasons.roster-packet.show');

            Route::get('submissions', [Submissions\SubmissionController::class, 'index'])->name('submissions.index');
            Route::get('submissions/{submission}', [Submissions\SubmissionController::class, 'show'])->name('submissions.show');
            Route::post('submissions/{submission}/review', [Submissions\SubmissionReviewController::class, 'store'])->name('submissions.review.store');
            Route::post('submissions/{submission}/dismissal', [Submissions\DismissedSubmissionController::class, 'store'])->name('submissions.dismissal.store');

            Route::resource('forms', Forms\FormController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
            Route::put('forms/{form}/draft', [Forms\FormDraftController::class, 'update'])->name('forms.draft.update');
            Route::post('forms/{form}/publication', [Forms\PublishedFormController::class, 'store'])->name('forms.publication.store');
            Route::delete('forms/{form}/publication', [Forms\PublishedFormController::class, 'destroy'])->name('forms.publication.destroy');

            Route::resource('players', Players\PlayerController::class);
            Route::post('players/{player}/restoration', [Players\RestoredPlayerController::class, 'store'])
                ->withTrashed()
                ->name('players.restoration.store');
            Route::post('players/{player}/guardians', [Players\PlayerGuardianController::class, 'store'])->name('players.guardians.store');
            Route::delete('players/{player}/guardians/{guardian}', [Players\PlayerGuardianController::class, 'destroy'])->name('players.guardians.destroy');
            Route::put('guardians/{guardian}', [Players\GuardianController::class, 'update'])->name('guardians.update');
            Route::post('guardians/{guardian}/claim-invitation', [Players\GuardianClaimInvitationController::class, 'store'])
                ->name('guardians.claim-invitation.store');

            Route::prefix('organization')->name('organization.')->group(function () {
                Route::resource('seasons', Organization\SeasonController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::put('active-season', [Organization\ActiveSeasonController::class, 'update'])->name('active-season.update');
                Route::get('season-rollover/create', [Organization\SeasonRolloverController::class, 'create'])->name('season-rollover.create');
                Route::post('season-rollover', [Organization\SeasonRolloverController::class, 'store'])->name('season-rollover.store');

                Route::resource('divisions', Organization\DivisionController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::put('division-order', [Organization\DivisionOrderController::class, 'update'])->name('division-order.update');

                Route::resource('locations', Organization\LocationController::class)->only(['index', 'store', 'update', 'destroy']);

                Route::get('members', [Organization\MemberController::class, 'index'])->name('members.index');
                Route::put('members/{member}', [Organization\MemberController::class, 'update'])->name('members.update');
                Route::delete('members/{member}', [Organization\MemberController::class, 'destroy'])->name('members.destroy');
                Route::post('invitations', [Organization\OrganizationInvitationController::class, 'store'])->name('invitations.store');
                Route::delete('invitations/{invitation}', [Organization\OrganizationInvitationController::class, 'destroy'])
                    ->whereNumber('invitation')
                    ->name('invitations.destroy');

                Route::get('payments', [Organization\PaymentSettingsController::class, 'show'])->name('payments.show');
                Route::post('stripe-account', [Organization\StripeAccountController::class, 'store'])->name('stripe-account.store');
                Route::get('stripe-account/return', [Organization\StripeAccountController::class, 'return'])->name('stripe-account.return');
                Route::get('stripe-account/refresh', [Organization\StripeAccountController::class, 'refresh'])->name('stripe-account.refresh');
                Route::delete('stripe-account', [Organization\StripeAccountController::class, 'destroy'])->name('stripe-account.destroy');

                Route::get('profile', [Organization\OrganizationProfileController::class, 'edit'])->name('profile.edit');
                Route::post('profile', [Organization\OrganizationProfileController::class, 'update'])->name('profile.update');
            });

            if (app()->isLocal()) {
                Route::inertia('dev/components', 'dev/components')->name('dev.components');
            }
        });
    });
});

Route::middleware(['auth', 'verified', 'platform-admin', 'organization'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function () {
        Route::get('organizations', [Platform\OrganizationController::class, 'index'])->name('organizations.index');
        Route::get('organizations/{organization}', [Platform\OrganizationController::class, 'show'])->name('organizations.show');
        Route::put('organizations/{organization}/trial', [Platform\OrganizationTrialController::class, 'update'])
            ->name('organizations.trial.update');
        Route::post('organizations/{organization}/support-session', [Platform\SupportSessionController::class, 'store'])
            ->name('organizations.support-session.store');
        Route::delete('support-session', [Platform\SupportSessionController::class, 'destroy'])
            ->name('support-session.destroy');
    });

require __DIR__.'/settings.php';

Route::middleware('public-organization')
    ->prefix('{organization:slug}/register/{form:slug}')
    ->scopeBindings()
    ->name('registration.')
    ->group(function () {
        Route::get('/', [PublicRegistrationController::class, 'show'])->name('show');
        Route::post('/', [PublicRegistrationController::class, 'store'])->middleware('throttle:registrations')->name('store');
        Route::get('thanks', [PublicRegistrationController::class, 'thanks'])->name('thanks');
    });

Route::get('{organization:slug}/pay/{payment}', [RegistrationPaymentController::class, 'show'])
    ->middleware(['public-organization', 'signed'])
    ->name('registration-payments.show');

// Registered last: a single path segment only reaches an organization's public
// page when no other route matched. Organization slugs never use a reserved
// first segment (see CreateOrganization).
Route::middleware('public-organization')->group(function () {
    Route::get('{organization:slug}', [PublicOrganizationController::class, 'show'])->name('organizations.public.show');
    Route::get('{organization:slug}/teams/{team}', [PublicOrganizationController::class, 'team'])->name('organizations.public.team');
});
