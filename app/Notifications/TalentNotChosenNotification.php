<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TalentNotChosenNotification extends Notification implements ShouldQueue
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
        $title = $this->serviceRequest->title ?? __('messages.mission', [], $this->locale);

        return (new MailMessage)
            ->subject(__('messages.email_not_chosen_subject', ['title' => $title], $this->locale))
            ->greeting(__('messages.email_greeting', ['name' => $notifiable->first_name], $this->locale))
            ->line(__('messages.email_not_chosen_body', ['title' => $title], $this->locale))
            ->line(__('messages.email_not_chosen_encourage', [], $this->locale))
            ->action(__('messages.email_not_chosen_action', [], $this->locale), url('/talent/requests'))
            ->salutation(__('messages.email_salutation', [], $this->locale));
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->serviceRequest->title ?? __('messages.mission', [], $this->locale);

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => __('messages.notif_not_chosen_title', ['title' => $title], $this->locale),
            'message' => __('messages.notif_not_chosen_body', ['title' => $title], $this->locale),
        ];
    }
}
