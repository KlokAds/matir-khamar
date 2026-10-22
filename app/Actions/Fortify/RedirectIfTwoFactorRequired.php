<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;

/**
 * Asks for the two-factor code at login only where the user's role requires it (Setup → Roles).
 * A role without the tick never stops anyone at a code, even if the person once switched it on;
 * the owner's super account keeps its own choice.
 */
class RedirectIfTwoFactorRequired extends RedirectIfTwoFactorAuthenticatable
{
    public function handle($request, $next)
    {
        $user = $this->validateCredentials($request);

        if ($user instanceof User && ! $user->is_super && ! $user->mustUseTwoFactor()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
