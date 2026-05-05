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
        $client = $this->serviceRequest->client;
        $category = $this->serviceRequest->category;

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title'              => 'Nouvelle demande de service',
            'message'            => sprintf(
                '%s %s cherche un %s à %s pour %s DH.',
                $client->first_name,
                $client->last_name,
                $category->name ?? 'service',
                $this->serviceRequest->city,
                number_format($this->serviceRequest->proposed_price, 0, ',', ' ')
            ),
            'tier'               => $this->tier,
            'city'               => $this->serviceRequest->city,
            'proposed_price'     => $this->serviceRequest->proposed_price,
        ];
    }
}
