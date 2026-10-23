<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('platform:grant-admin {email : The email address of an existing user} {--revoke : Remove platform admin access instead}')]
#[Description('Grant or revoke platform admin access for a user')]
class GrantPlatformAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No user has that email address.');

            return self::FAILURE;
        }

        $isGranting = ! $this->option('revoke');

        $user->forceFill(['is_platform_admin' => $isGranting])->save();

        $this->info($isGranting
            ? "{$user->email} is now a platform admin."
            : "{$user->email} is no longer a platform admin.");

        return self::SUCCESS;
    }
}
