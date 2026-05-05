<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\NewServiceRequestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyRemainingProviders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly array $excludedProviderIds
    ) {}

    /**
     * Execute the job.
     * Runs after 30 minutes delay - notifies the remaining providers in the city.
     */
    public function handle(): void
    {
        // If the request is already accepted (no longer pending), skip
        if ($this->serviceRequest->status !== 'pending') {
            return;
        }

        $remainingProviders = User::where('role', 'provider')
            ->where('is_verified_student', true)
            ->where('city', $this->serviceRequest->city)
            ->whereNotIn('id', $this->excludedProviderIds)
            ->get();

        foreach ($remainingProviders as $provider) {
            $provider->notify(new NewServiceRequestNotification($this->serviceRequest, 'others'));
        }
    }
}
