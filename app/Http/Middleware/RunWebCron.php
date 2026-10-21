<?php

namespace App\Http\Middleware;

use App\Support\WebCron;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts the scheduler from web traffic (see App\Support\WebCron). Runs in terminate(), after
 * the response has gone out, so nobody waits for it.
 */
class RunWebCron
{
    public function __construct(private WebCron $cron) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->cron->tick();
    }
}
