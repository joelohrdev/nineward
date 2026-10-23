<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Old invitations (see OrganizationInvitation::prunable()).
Schedule::command('model:prune')->daily()->withoutOverlapping();

// Stripe only redelivers webhook events for a few days, so a record of
// handled events older than that can go.
Schedule::call(fn () => DB::table('processed_stripe_events')->where('processed_at', '<', now()->subDays(90))->delete())
    ->name('prune-processed-stripe-events')
    ->daily()
    ->withoutOverlapping();

// Roster packets hold minors' contact details; their download links last a
// day, so the files don't need to outlive that by much.
Schedule::call(function (): void {
    $disk = Storage::disk('local');

    foreach ($disk->allFiles('roster-packets') as $path) {
        if ($disk->lastModified($path) < now()->subDays(2)->getTimestamp()) {
            $disk->delete($path);
        }
    }
})->name('prune-roster-packets')->daily()->withoutOverlapping();
