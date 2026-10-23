<?php

namespace App\Notifications;

use App\Models\Guardian;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class GuardianClaimInvitationSent extends Notification implements ShouldQueue
{
    use Queueable;

    public const VALID_FOR_DAYS = 7;

    public function __construct(public Guardian $guardian)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $organization = $this->guardian->organization;
        $url = URL::temporarySignedRoute('guardian-claims.show', now()->addDays(self::VALID_FOR_DAYS), ['guardian' => $this->guardian->id]);

        return (new MailMessage)
            ->subject("See your players at {$organization->name}")
            ->greeting("Hi {$this->guardian->first_name},")
            ->line("{$organization->name} uses ".config('app.name').' for registration and rosters. Create an account or log in with this email address to see your players.')
            ->action('Set up your account', $url)
            ->line('This link works for '.self::VALID_FOR_DAYS.' days.');
    }
}
