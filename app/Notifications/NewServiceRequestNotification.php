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
        return ['database'];
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
