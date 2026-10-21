<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out disabled users at once, and sends anyone whose role moves money
 * to the security page until two-factor login is switched on.
 */
class EnsureAccountIsSecure
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'এই অ্যাকাউন্টটি বন্ধ করা হয়েছে।']);
        }

        $mustEnroll = config('khamar.require_two_factor')
            && $user->role->requiresTwoFactor()
            && ! $user->hasTwoFactorEnabled();

        if ($mustEnroll && ! $request->routeIs('security.edit', 'two-factor.*', 'password.confirm*', 'logout', 'user-password.update', 'passkey*', 'appearance.edit')) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'হিসাব রক্ষার জন্য আগে ২-ধাপ যাচাই (2FA) চালু করুন।']);

            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
