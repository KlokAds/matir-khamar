<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role key of a row in the roles table
 * @property bool $is_active
 * @property bool $is_super
 * @property string $locale bn or en
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property CarbonImmutable|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AccessRole|null $accessRole
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'is_super' => 'boolean',
        ];
    }

    /**
     * Accepts a built-in Role case or any role key.
     *
     * @return Attribute<string, Role|string>
     */
    protected function role(): Attribute
    {
        return Attribute::make(set: fn (Role|string $value) => $value instanceof Role ? $value->value : $value);
    }

    /** @return BelongsTo<AccessRole, $this> */
    public function accessRole(): BelongsTo
    {
        return $this->belongsTo(AccessRole::class, 'role', 'key');
    }

    /**
     * What this user may do. The owner's super account may do everything; a disabled user
     * or one whose role was removed may do nothing.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        if (! $this->is_active) {
            return [];
        }

        if ($this->is_super) {
            return array_keys(Role::ABILITIES);
        }

        return $this->accessRole?->grantedAbilities() ?? [];
    }

    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities(), true);
    }

    public function mustUseTwoFactor(): bool
    {
        return ! $this->is_super && (bool) $this->accessRole?->requires_two_factor;
    }

    public function roleLabel(): string
    {
        return $this->is_super ? 'সুপার অ্যাডমিন' : ($this->accessRole->name ?? $this->role);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }
}
