<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A role and the abilities it grants. The built-in admin role always keeps every ability, so
 * nobody can lock the farm out of its own settings.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property list<string> $abilities
 * @property bool $requires_two_factor
 * @property bool $is_system
 * @property int $sort
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['key', 'name', 'abilities', 'requires_two_factor', 'is_system', 'sort'])]
class AccessRole extends Model
{
    use Auditable;

    protected $table = 'roles';

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'requires_two_factor' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->key === Role::Admin->value;
    }

    /** @return list<string> */
    public function grantedAbilities(): array
    {
        return $this->isAdmin() ? array_keys(Role::ABILITIES) : array_values(array_intersect($this->abilities, array_keys(Role::ABILITIES)));
    }

    /** @return array<string, string> key => name, in display order */
    public static function options(): array
    {
        return static::query()->orderBy('sort')->orderBy('name')->pluck('name', 'key')->all();
    }
}
