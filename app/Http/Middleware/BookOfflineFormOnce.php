<?php

namespace App\Http\Middleware;

use App\Models\OfflineSubmission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * A form kept on a phone with no internet arrives later carrying the id the phone gave it
 * (X-Offline-Key). If the net dropped after the server had booked it, the phone sends it
 * again: that second time it is answered as done and not booked twice.
 */
class BookOfflineFormOnce
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('X-Offline-Key');

        if ($request->isMethodSafe() || $request->user() === null || ! preg_match('/^[0-9a-f-]{36}$/i', $key)) {
            return $next($request);
        }

        if (OfflineSubmission::query()->where('uuid', $key)->where('user_id', $request->user()->id)->exists()) {
            return back(303);
        }

        $response = $next($request);

        // Refused (its errors are flashed for the page by this request) or failed: the phone
        // shows why and may send it again.
        $refused = in_array('errors', (array) $request->session()->get('_flash.new', []), true);

        if ($response->getStatusCode() >= 400 || $refused) {
            return $response;
        }

        try {
            $at = Date::parse((string) $request->header('X-Offline-At'));
        } catch (Throwable) {
            $at = now();
        }

        OfflineSubmission::query()->create([
            'uuid' => $key,
            'user_id' => $request->user()->id,
            'kind' => 'form',
            'payload' => ['method' => $request->method(), 'path' => $request->path()],
            'status' => 'done',
            'recorded_at' => $at,
        ]);

        return $response;
    }
}
