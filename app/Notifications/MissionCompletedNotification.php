<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MissionCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ServiceRequest $serviceRequest
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Send as email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->serviceRequest->title ?? 'Votre mission';
        $providerName = $this->serviceRequest->selectedProvider
            ? trim($this->serviceRequest->selectedProvider->first_name . ' ' . $this->serviceRequest->selectedProvider->last_name)
            : 'Le talent';

        return (new MailMessage)
            ->subject("✅ AjiKhdam — Votre mission est terminée : {$title}")
            ->greeting("Bonjour {$notifiable->first_name} !")
            ->line("**{$providerName}** a marqué votre mission **{$title}** comme terminée.")
            ->line('Laissez un avis pour aider les autres clients à choisir ce talent !')
            ->action('Laisser un avis', url("/requests/{$this->serviceRequest->id}"))
            ->salutation('À très bientôt — L\'équipe AjiKhdam 🇲🇦');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->serviceRequest->title ?? 'Votre mission';
        $providerName = $this->serviceRequest->selectedProvider
            ? trim($this->serviceRequest->selectedProvider->first_name . ' ' . $this->serviceRequest->selectedProvider->last_name)
            : 'Le talent';

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => $title,
            'provider_name' => $providerName,
            'message' => "Votre mission '{$title}' est terminée ! Laissez un avis au talent.",
        ];
    }
}
