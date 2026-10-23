<?php

namespace App\Notifications;

use App\Models\Player;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Lets owners and admins know a registration came in.
 */
class NewRegistrationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Submission $submission, public Player $player)
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
        $form = $this->submission->form;

        $message = (new MailMessage)
            ->subject("New registration: {$this->player->fullName()}")
            ->line("{$this->player->fullName()} registered through {$form->title}.");

        if ($this->submission->review_reason !== null) {
            $message->line("Needs a look: {$this->submission->review_reason}");
        }

        return $message->action('Open the registration', route('submissions.show', $this->submission));
    }
}
