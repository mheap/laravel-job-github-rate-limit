<?php

namespace App\Jobs;

use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class QueryGitHub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            dump(GitHub::me()->organizations());
        } catch (\Github\Exception\RuntimeException$e) {
            // If there's an exception, check our rate limit
            $limits = GitHub::api('rate_limit')->getResources();
            $reset = $limits['core']->getReset();

            // If there are no more requests available, add a cache entry
            if ($limits['core']->getRemaining() <= 0) {
                Cache::add('github-rate-limit-exceeded', $reset, ($reset - time()));
            }

            // Rethrow the exception to mark the job as failed
            throw $e;
        }
    }
}
