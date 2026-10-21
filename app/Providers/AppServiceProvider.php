<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureLoginAudit();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * One gate per ability, answered by the user's role.
     */
    protected function configureGates(): void
    {
        $abilities = collect(Role::cases())->flatMap(fn (Role $role) => $role->abilities())->unique();

        foreach ($abilities as $ability) {
            Gate::define($ability, fn (User $user) => $user->is_active && in_array($ability, $user->role->abilities(), true));
        }
    }

    /**
     * Every login, logout and failed attempt lands in the audit log.
     */
    protected function configureLoginAudit(): void
    {
        // The guard hands over the auth contract; every user here is an Eloquent User.
        Event::listen(function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->saveQuietly();
                AuditLog::record('login', $event->user, null, null);
            }
        });

        Event::listen(function (Logout $event) {
            if ($event->user instanceof User) {
                AuditLog::record('logout', $event->user, null, null);
            }
        });

        Event::listen(function (Failed $event) {
            AuditLog::record('login_failed', $event->user instanceof User ? $event->user : null, null, ['email' => request()->input('email')]);
        });
    }
}
