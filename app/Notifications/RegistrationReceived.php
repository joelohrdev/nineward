<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * The confirmation a family gets after registering, with a link to set up
 * an account if they don't have one yet.
 */
class RegistrationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Submission $submission)
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
        $organization = $form->organization;
        $guardian = $this->submission->guardian;
        $player = $this->submission->player;
        $payment = Payment::query()->whereBelongsTo($this->submission)->latest('id')->first();

        $message = (new MailMessage)
            ->subject("{$player?->first_name} is registered with {$organization->name}")
            ->greeting("Thanks, {$guardian?->first_name}.")
            ->line("{$organization->name} received {$player?->fullName()}'s registration for {$form->title}.");

        $feeIsDue = $payment !== null && $payment->status->isOutstanding();

        if ($feeIsDue) {
            $message->line('The registration fee of $'.number_format($payment->amount_cents / 100, 2).' is still due.')
                ->action('Pay the registration fee', $payment->payUrl($organization));
        }

        if ($guardian === null || $guardian->isClaimed()) {
            return $message->line("You'll hear from {$organization->name} when teams are set.");
        }

        $claimUrl = URL::temporarySignedRoute(
            'guardian-claims.show',
            now()->addDays(GuardianClaimInvitationSent::VALID_FOR_DAYS),
            ['guardian' => $guardian->id],
        );

        // A mail message has one button; when the fee is due, paying gets it.
        return $feeIsDue
            ? $message->line("Create an account with this email address to see your players and their teams: {$claimUrl}")
            : $message->line('Create an account with this email address to see your players and their teams.')
                ->action('Set up your account', $claimUrl);
    }
}
