<?php

namespace App\Http\Middleware;

use App\Models\Reminder;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('khamar.name'),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'email_verified_at', 'two_factor_confirmed_at', 'created_at', 'updated_at']),
                'role' => $request->user()?->role,
                'roleLabel' => $request->user() ? __($request->user()->roleLabel()) : null,
                'can' => $request->user() ? array_fill_keys($request->user()->abilities(), true) : [],
            ],
            // Open reminders due by the end of today, for the bell in the top bar.
            'remindersDue' => fn () => $request->user()
                ? Reminder::query()->visibleTo($request->user())->where('due_at', '<=', now()->endOfDay())->count()
                : 0,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
