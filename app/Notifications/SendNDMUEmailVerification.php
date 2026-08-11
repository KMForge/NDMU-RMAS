<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class SendNDMUEmailVerification extends BaseVerifyEmail
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your NDMU-RMAS Student Account (@ndmu.edu.ph)')
            ->greeting("Hello, {$notifiable->name}!")
            ->line('Thank you for registering on the Notre Dame of Marbel University Research Management and Assistance System.')
            ->line('Please click the button below to verify your institutional email address (@ndmu.edu.ph).')
            ->action('Verify Institutional Email', $verificationUrl)
            ->line('Note: Verification is required before your student account can be activated by the administration.')
            ->line('If you did not create an NDMU-RMAS account, no further action is required.');
    }
}
