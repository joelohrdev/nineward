<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public OrganizationInvitation $invitation)
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
        $organization = $this->invitation->organization;
        $role = mb_strtolower($this->invitation->role->label());

        return (new MailMessage)
            ->subject("Join {$organization->name} on ".config('app.name'))
            ->greeting("You're invited to {$organization->name}")
            ->line("{$this->invitation->inviter->name} invited you to help run {$organization->name} as a {$role}.")
            ->action('Accept invitation', $this->invitation->url())
            ->line('This invitation expires on '.$this->invitation->expires_at->format('F j, Y').'.');
    }
}
