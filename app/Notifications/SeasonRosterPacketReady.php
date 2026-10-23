<?php

namespace App\Notifications;

use App\Models\Season;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class SeasonRosterPacketReady extends Notification implements ShouldQueue
{
    use Queueable;

    public const LINK_VALID_FOR_HOURS = 24;

    public function __construct(public Season $season) {}

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
        return (new MailMessage)
            ->subject("{$this->season->name} rosters are ready")
            ->line("Every {$this->season->name} roster is in one PDF, with parents' contact details.")
            ->action('Download the rosters', URL::temporarySignedRoute(
                'seasons.roster-packet.show',
                now()->addHours(self::LINK_VALID_FOR_HOURS),
                ['season' => $this->season->id],
            ))
            ->line('The link works for '.self::LINK_VALID_FOR_HOURS.' hours and only for people who manage your organization.');
    }
}
