<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AccessRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Setup → Roles: which role may do what, and whether it needs two-factor login. */
class RoleController extends Controller
{
    public function index(): Response
    {
        $counts = User::query()->where('is_super', false)->selectRaw('role, COUNT(*) as n')->groupBy('role')->pluck('n', 'role');

        return Inertia::render('setup/Roles', [
            'roles' => AccessRole::query()->orderBy('sort')->orderBy('name')->get()->map(fn (AccessRole $role) => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => $role->name,
                'abilities' => $role->grantedAbilities(),
                'requires_two_factor' => $role->requires_two_factor,
                'is_system' => $role->is_system,
                'is_admin' => $role->isAdmin(),
                'users' => (int) ($counts[$role->key] ?? 0),
            ]),
            'abilities' => Role::ABILITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        AccessRole::query()->create([
            ...$data,
            'key' => $this->uniqueKey($data['name']),
            'is_system' => false,
            'sort' => (int) AccessRole::query()->max('sort') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "নতুন রোল \"{$data['name']}\" তৈরি হয়েছে।"]);

        return back();
    }

    public function update(Request $request, AccessRole $role): RedirectResponse
    {
        $data = $this->validated($request);

        // The admin role keeps every ability, so the farm can never lock itself out.
        if ($role->isAdmin()) {
            $data['abilities'] = array_keys(Role::ABILITIES);
        }

        $role->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => "\"{$role->name}\" রোল আপডেট হয়েছে।"]);

        return back();
    }

    public function destroy(AccessRole $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'শুরু থেকে থাকা রোল মোছা যায় না।']);
        }

        if (User::query()->where('role', $role->key)->exists()) {
            return back()->withErrors(['role' => 'এই রোলে এখনো ব্যবহারকারী আছে। আগে তাঁদের অন্য রোলে নিন।']);
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'রোল মুছে ফেলা হয়েছে।']);

        return back();
    }

    /** @return array{name: string, abilities: list<string>, requires_two_factor: bool} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['present', 'array'],
            'abilities.*' => ['string', Rule::in(array_keys(Role::ABILITIES))],
            'requires_two_factor' => ['required', 'boolean'],
        ]);

        return [
            'name' => (string) $data['name'],
            'abilities' => array_values(array_unique(array_map('strval', $data['abilities']))),
            'requires_two_factor' => (bool) $data['requires_two_factor'],
        ];
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug(Str::transliterate($name)) ?: 'role';
        $base = Str::limit($base, 24, '');
        $key = $base;

        for ($i = 2; AccessRole::query()->where('key', $key)->exists(); $i++) {
            $key = $base.'-'.$i;
        }

        return $key;
    }
}
