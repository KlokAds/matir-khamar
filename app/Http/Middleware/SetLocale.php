<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bangla or English for this request: the signed-in person's choice, or for the login page the
 * choice remembered in the session. Messages, validation errors and page text all follow it.
 */
class SetLocale
{
    public const LOCALES = ['bn', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()->locale ?? $request->session()->get('locale', config('app.locale'));

        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'bn');

        return $next($request);
    }
}
