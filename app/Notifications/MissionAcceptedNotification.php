<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MissionAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly User $talent
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
        $talentName = trim($this->talent->first_name . ' ' . $this->talent->last_name);

        return (new MailMessage)
            ->subject("🎉 AjiKhdam — Un talent a accepté votre mission : {$title}")
            ->greeting("Bonjour {$notifiable->first_name} !")
            ->line("**{$talentName}** a accepté votre mission **{$title}**.")
            ->action('Voir les détails de la mission', url("/requests/{$this->serviceRequest->id}"))
            ->line('Votre mission est maintenant en cours.')
            ->salutation('À très bientôt — L\'équipe AjiKhdam 🇲🇦');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->serviceRequest->title ?? 'Votre mission';
        $talentName = trim($this->talent->first_name . ' ' . $this->talent->last_name);

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => $title,
            'talent_name' => $talentName,
            'message' => "Un talent a accepté votre mission : {$title}",
        ];
    }
}
