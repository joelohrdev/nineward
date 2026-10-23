<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StripeAccountDisconnected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Organization $organization)
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
        return (new MailMessage)
            ->subject("{$this->organization->name}'s Stripe account was disconnected")
            ->line('Your Stripe account is no longer connected, so registration forms with a fee are paused.')
            ->action('Reconnect Stripe', route('organization.payments.show'))
            ->line('Forms without a fee keep working.');
    }
}
