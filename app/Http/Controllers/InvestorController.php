<?php

namespace App\Http\Controllers;

use App\Models\InvestorAccess;
use App\Models\Party;
use App\Models\User;
use App\Services\InvestorBook;
use App\Support\Lookups;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The investor's own read-only page, and the owner's list of who may see which project.
 */
class InvestorController extends Controller
{
    /** The investor's page: only the projects granted to this login. */
    public function index(Request $request, InvestorBook $book): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $book->hasAccess($user), 403);

        return Inertia::render('investor/Index', [
            'farm' => config('khamar.name'),
            'investor' => $user->name,
            'asOf' => now()->toDateString(),
            ...$book->forUser($user),
        ]);
    }

    /** Setup → Investors: who sees which project, with what share. */
    public function manage(InvestorBook $book): Response
    {
        $grants = InvestorAccess::query()->with(['costCenter:id,name', 'party:id,name'])->get()->groupBy('user_id');

        $users = User::query()
            ->where('is_super', false)
            ->where(fn ($q) => $q->where('role', 'investor')->orWhereIn('id', $grants->keys()))
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($grants, $book) {
                $rows = $grants->get($user->id, collect());
                /** @var list<int> $partyIds */
                $partyIds = $rows->pluck('party_id')->filter()->unique()->map(fn ($id) => (int) $id)->values()->all();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'role_label' => Tr::get($user->roleLabel()),
                    'can_view' => $user->hasAbility('investor-view'),
                    'capital' => $book->capital($partyIds)['net'],
                    'grants' => $rows->map(fn (InvestorAccess $a) => [
                        'id' => $a->id,
                        'center_id' => $a->cost_center_id,
                        'center' => $a->costCenter->name,
                        'party_id' => $a->party_id,
                        'party' => $a->party?->name,
                        'share_percent' => $a->share_percent,
                        'since' => $a->since?->toDateString(),
                    ])->values(),
                ];
            });

        return Inertia::render('setup/Investors', [
            'users' => $users,
            'parties' => Party::query()->where('type', 'investor')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'centers' => Lookups::centers(),
            'shareTaken' => InvestorAccess::query()->whereNotNull('share_percent')->groupBy('cost_center_id')
                ->selectRaw('cost_center_id, SUM(share_percent) as total')->pluck('total', 'cost_center_id')
                ->map(fn ($total) => (float) $total),
        ]);
    }

    /** Give a login a project to see (or change the share of one it already sees). */
    public function grant(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('is_super', false)],
            'cost_center_id' => ['required', 'exists:cost_centers,id'],
            'party_id' => ['nullable', Rule::exists('parties', 'id')->where('type', 'investor')],
            'share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'since' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $share = isset($data['share_percent']) && $data['share_percent'] !== '' ? round((float) $data['share_percent'], 2) : null;

        if ($share !== null) {
            $others = (float) InvestorAccess::query()->where('cost_center_id', $data['cost_center_id'])
                ->where('user_id', '!=', $data['user_id'])->sum('share_percent');

            if ($others + $share > 100) {
                return back()->withErrors(['share_percent' => Tr::get('এই প্রজেক্টে অন্যদের ভাগ মিলে :n%, তাই :left% এর বেশি দেওয়া যাবে না।', [
                    'n' => round($others, 2), 'left' => round(max(0, 100 - $others), 2),
                ])]);
            }
        }

        InvestorAccess::query()->updateOrCreate(
            ['user_id' => $data['user_id'], 'cost_center_id' => $data['cost_center_id']],
            ['party_id' => $data['party_id'] ?? null, 'share_percent' => $share, 'since' => $data['since'] ?? null],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('বিনিয়োগকারী এখন এই প্রজেক্টের হিসাব দেখতে পারবেন।')]);

        return back();
    }

    public function revoke(InvestorAccess $access): RedirectResponse
    {
        $access->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('এই প্রজেক্ট আর দেখতে পারবেন না।')]);

        return back();
    }
}
