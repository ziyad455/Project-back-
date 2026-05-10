<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewServiceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly string $tier = 'top'  // 'top' for top 10, 'others' for the rest
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
        $category  = $this->serviceRequest->category;
        $clientName = $this->serviceRequest->client
            ? ($this->serviceRequest->client->first_name . ' ' . $this->serviceRequest->client->last_name)
            : ($this->serviceRequest->client_name ?? 'Un client');

        $price = $this->serviceRequest->budget ?? $this->serviceRequest->proposed_price;
        $title = $this->serviceRequest->title ?? ($category->name ?? 'Service');
        $city  = $this->serviceRequest->city;

        $badge = $this->tier === 'top'
            ? '⭐ Vous faites partie des meilleurs talents — vous avez été notifié en priorité !'
            : '📢 Nouvelle mission disponible pour vous.';

        return (new MailMessage)
            ->subject("🚀 AjiKhdam — Nouvelle mission : {$title}")
            ->greeting("Bonjour {$notifiable->first_name} !")
            ->line($badge)
            ->line("**{$clientName}** recherche un **{$category?->name ?? 'prestataire'}** à **{$city}** pour **{$price} DH**.")
            ->when($this->serviceRequest->description, fn($mail) =>
                $mail->line("Description : " . \Str::limit($this->serviceRequest->description, 200))
            )
            ->action('Voir la mission sur AjiKhdam', url("/requests/{$this->serviceRequest->id}"))
            ->line('Connectez-vous à votre tableau de bord pour envoyer votre offre.')
            ->salutation('À très bientôt — L\'équipe AjiKhdam 🇲🇦');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $category = $this->serviceRequest->category;
        
        $clientName = $this->serviceRequest->client 
            ? ($this->serviceRequest->client->first_name . ' ' . $this->serviceRequest->client->last_name)
            : ($this->serviceRequest->client_name ?? 'Un client');

        $price = $this->serviceRequest->budget ?? $this->serviceRequest->proposed_price;
        $title = $this->serviceRequest->title ?? ($category->name ?? 'Service');

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title'              => 'Nouvelle mission: ' . $title,
            'message'            => sprintf(
                '%s cherche un %s à %s pour %s DH.',
                $clientName,
                $category->name ?? 'service',
                $this->serviceRequest->city,
                number_format($price, 0, ',', ' ')
            ),
            'tier'               => $this->tier,
            'city'               => $this->serviceRequest->city,
            'proposed_price'     => $price,
        ];
    }
}
