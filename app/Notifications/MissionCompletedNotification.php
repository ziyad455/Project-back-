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
        public readonly ServiceRequest $serviceRequest,
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
        $title = $this->serviceRequest->title ?? 'Votre mission';
        $providerName = $this->serviceRequest->selectedProvider
            ? trim($this->serviceRequest->selectedProvider->first_name . ' ' . $this->serviceRequest->selectedProvider->last_name)
            : 'Le talent';

        return (new MailMessage)
            ->subject(__('messages.email_completed_subject', ['title' => $title], $this->locale))
            ->greeting(__('messages.email_greeting', ['name' => $notifiable->first_name], $this->locale))
            ->line(__('messages.email_completed_body', ['provider' => $providerName, 'title' => $title], $this->locale))
            ->line(__('messages.email_completed_review', [], $this->locale))
            ->action(__('messages.email_completed_action', [], $this->locale), url("/requests/{$this->serviceRequest->id}"))
            ->salutation(__('messages.email_salutation', [], $this->locale));
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->serviceRequest->title ?? 'Votre mission';
        $providerName = $this->serviceRequest->selectedProvider
            ? trim($this->serviceRequest->selectedProvider->first_name . ' ' . $this->serviceRequest->selectedProvider->last_name)
            : 'Le talent';

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => __('messages.notif_completed_title', ['title' => $title], $this->locale),
            'provider_name' => $providerName,
            'message' => __('messages.notif_completed_body', ['title' => $title], $this->locale),
        ];
    }
}
