<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TalentChosenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly ?User $client,
        string $locale = 'fr'
    ) {
        $this->locale = $locale;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->serviceRequest->title ?? __('messages.mission', [], $this->locale);
        $clientName = $this->client
            ? trim($this->client->first_name . ' ' . $this->client->last_name)
            : ($this->serviceRequest->client_name ?? __('messages.client', [], $this->locale));

        $whatsappNumber = $this->client?->whatsapp_number
            ?? $this->serviceRequest->guest_whatsapp_number
            ?? $this->serviceRequest->client_phone
            ?? null;

        $whatsappLink = $whatsappNumber
            ? "https://wa.me/{$whatsappNumber}?text=" . urlencode(__('messages.whatsapp_greeting', [], $this->locale)) . '%20' . urlencode($title)
            : null;

        return (new MailMessage)
            ->subject(__('messages.email_chosen_subject', ['title' => $title], $this->locale))
            ->greeting(__('messages.email_greeting', ['name' => $notifiable->first_name], $this->locale))
            ->line(__('messages.email_chosen_body', ['title' => $title, 'client' => $clientName], $this->locale))
            ->when($whatsappLink, fn($mail) =>
                $mail->action(__('messages.email_chosen_whatsapp', [], $this->locale), $whatsappLink)
            )
            ->line(__('messages.email_chosen_footer', [], $this->locale))
            ->salutation(__('messages.email_salutation', [], $this->locale));
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->serviceRequest->title ?? __('messages.mission', [], $this->locale);
        $clientName = $this->client
            ? trim($this->client->first_name . ' ' . $this->client->last_name)
            : ($this->serviceRequest->client_name ?? __('messages.client', [], $this->locale));

        $whatsappNumber = $this->client?->whatsapp_number
            ?? $this->serviceRequest->guest_whatsapp_number
            ?? $this->serviceRequest->client_phone
            ?? null;

        $whatsappLink = $whatsappNumber
            ? "https://wa.me/{$whatsappNumber}?text=" . urlencode(__('messages.whatsapp_greeting', [], $this->locale)) . '%20' . urlencode($title)
            : null;

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => __('messages.notif_chosen_title', ['title' => $title], $this->locale),
            'client_name' => $clientName,
            'whatsapp_link' => $whatsappLink,
            'message' => __('messages.notif_chosen_body', ['title' => $title, 'client' => $clientName], $this->locale),
        ];
    }
}
