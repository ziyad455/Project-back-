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
        public readonly string $temporaryPassword
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Send as email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🚀 Bienvenue sur AjiKhdam — Votre compte a été créé")
            ->greeting("Bonjour {$notifiable->first_name} !")
            ->line("Merci d'avoir posté votre mission sur AjiKhdam.")
            ->line("Un compte client a été créé pour vous afin de suivre l'avancement de vos demandes.")
            ->line("**Voici vos identifiants de connexion :**")
            ->line("- **Email :** {$notifiable->email}")
            ->line("- **Mot de passe temporaire :** {$this->temporaryPassword}")
            ->action('Se connecter au tableau de bord', url("/login"))
            ->line("Nous vous conseillons de modifier votre mot de passe dès votre première connexion.")
            ->salutation('À très bientôt — L\'équipe AjiKhdam 🇲🇦');
    }
}
