<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeGuestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $temporaryPassword,
        string $locale = 'fr'
    ) {
        $this->locale = $locale;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.email_welcome_subject', [], $this->locale))
            ->greeting(__('messages.email_greeting', ['name' => $notifiable->first_name], $this->locale))
            ->line(__('messages.email_welcome_line1', [], $this->locale))
            ->line(__('messages.email_welcome_line2', [], $this->locale))
            ->line(__('messages.email_welcome_credentials', [], $this->locale))
            ->line(__('messages.email_welcome_email_label', ['email' => $notifiable->email], $this->locale))
            ->line(__('messages.email_welcome_password_label', ['password' => $this->temporaryPassword], $this->locale))
            ->action(__('messages.email_welcome_action', [], $this->locale), url("/login"))
            ->line(__('messages.email_welcome_advice', [], $this->locale))
            ->salutation(__('messages.email_salutation', [], $this->locale));
    }
}
