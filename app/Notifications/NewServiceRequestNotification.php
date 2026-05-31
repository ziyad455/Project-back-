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
        public readonly string $tier = 'top',
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
        $category  = $this->serviceRequest->category;
        $clientName = $this->serviceRequest->client
            ? ($this->serviceRequest->client->first_name . ' ' . $this->serviceRequest->client->last_name)
            : ($this->serviceRequest->client_name ?? 'Un client');

        $price = $this->serviceRequest->budget ?? $this->serviceRequest->proposed_price;
        $title = $this->serviceRequest->title ?? ($category->name ?? 'Service');
        $categoryName = $category->name ?? 'prestataire';
        $city  = $this->serviceRequest->city;

        $badge = $this->tier === 'top'
            ? __('messages.email_new_mission_badge_top', [], $this->locale)
            : __('messages.email_new_mission_badge_others', [], $this->locale);

        $mail = (new MailMessage)
            ->subject(__('messages.email_new_mission_subject', ['title' => $title], $this->locale))
            ->greeting(__('messages.email_greeting', ['name' => $notifiable->first_name], $this->locale))
            ->line($badge)
            ->line(__('messages.email_new_mission_body', [
                'client' => $clientName,
                'category' => $categoryName,
                'city' => $city,
                'price' => number_format((int)$price, 0, ',', ' '),
            ], $this->locale))
            ->when($this->serviceRequest->description, fn($mail) =>
                $mail->line(__('messages.email_new_mission_desc', ['desc' => \Str::limit($this->serviceRequest->description, 200)], $this->locale))
            )
            ->action(__('messages.email_new_mission_action', [], $this->locale), url('/talent/requests'))
            ->line(__('messages.email_new_mission_footer', [], $this->locale))
            ->salutation(__('messages.email_salutation', [], $this->locale));

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $category = $this->serviceRequest->category;
        $client = $this->serviceRequest->client;

        $clientName = $client
            ? trim($client->first_name.' '.$client->last_name)
            : ($this->serviceRequest->client_name ?? $this->serviceRequest->guest_name ?? 'Un client');
        $price = $this->serviceRequest->budget ?? $this->serviceRequest->proposed_price;
        $title = $this->serviceRequest->title ?? ($category->name ?? 'Service');

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title'              => __('messages.notif_new_mission_title', ['title' => $title], $this->locale),
            'message'            => __('messages.notif_new_mission_body', [
                'client' => $clientName,
                'category' => $category->name ?? 'service',
                'city' => $this->serviceRequest->city,
                'price' => number_format((int)$price, 0, ',', ' '),
            ], $this->locale),
            'tier'               => $this->tier,
            'city'               => $this->serviceRequest->city,
            'proposed_price'     => $price,
        ];
    }
}
