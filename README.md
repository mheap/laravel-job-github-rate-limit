# Laravel / GitHub Queue rate limit

This is a sample repo to test how to stop a Laravel queue running when you've hit your GitHub rate limit.

## How it works

When your GitHub API request fails, the job checks our current rate limit to see if we've hit our rate limit. If we have, it creates a new cache entry with an expiry time of `reset_time - now` seconds.

```php
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
```

This cache entry can then be checked before a queue worker takes a job to see if it should execute:

```php
Queue::looping(function (\Illuminate\Queue\Events\Looping$event) {
    // If there's a circuit breaker set on the github queue, don't execute the job
    if (($event->queue == 'github') && (Cache::has('github-rate-limit-exceeded'))) {
        dump("Rate limit hit, resetting in: " . Cache::get('github-rate-limit-exceeded') - time());
        return false;
    }

    return true;
});
```

If the `looping` method returns false, the queue does not run. In production you'll probably want to remove the `dump` line.

## Testing

To test, trigger a job:

```php
\App\Jobs\QueryGitHub::dispatch()->onQueue('github');
```

Wrap the above in a loop if you want to exhaust your rate limit and see what happens:

```php
foreach(range(1,5000) as $r){
  \App\Jobs\QueryGitHub::dispatch()->onQueue('github');
}
```
