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
        public readonly User $talent,
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
        $talentName = trim($this->talent->first_name . ' ' . $this->talent->last_name);

        return (new MailMessage)
            ->subject(__('messages.email_accepted_subject', ['title' => $title], $this->locale))
            ->greeting(__('messages.email_greeting', ['name' => $notifiable->first_name], $this->locale))
            ->line(__('messages.email_accepted_body', ['talent' => $talentName, 'title' => $title], $this->locale))
            ->action(__('messages.email_accepted_action', [], $this->locale), url("/requests/{$this->serviceRequest->id}"))
            ->line(__('messages.email_accepted_footer', [], $this->locale))
            ->salutation(__('messages.email_salutation', [], $this->locale));
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->serviceRequest->title ?? 'Votre mission';
        $talentName = trim($this->talent->first_name . ' ' . $this->talent->last_name);

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => __('messages.notif_accepted_title', ['title' => $title], $this->locale),
            'talent_name' => $talentName,
            'message' => __('messages.notif_accepted_body', ['title' => $title], $this->locale),
        ];
    }
}
