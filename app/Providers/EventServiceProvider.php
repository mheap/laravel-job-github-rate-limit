<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Queue::looping(function (\Illuminate\Queue\Events\Looping$event) {

            // If there's a circuit breaker set on the github queue, don't execute the job
            if (($event->queue == 'github') && (Cache::has('github-rate-limit-exceeded'))) {
                dump("Rate limit hit, resetting in: " . Cache::get('github-rate-limit-exceeded') - time());
                return false;
            }

            return true;
        });
    }
}
