<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\MilkRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Daily milk yield, morning and evening. Sales are entered as income. */
class MilkController extends Controller
{
    public function index(): Response
    {
        $gavi = CostCenter::query()->where('code', 'GAVI')->first();

        return Inertia::render('milk/Index', [
            'sessions' => MilkRecord::SESSIONS,
            'cows' => Animal::query()->where('status', 'active')->where('sex', 'female')->when($gavi, fn ($query) => $query->where('cost_center_id', $gavi->id))->orderBy('tag')->get(['id', 'tag', 'name']),
            'days' => MilkRecord::query()
                ->whereDate('date', '>=', now()->subDays(30))
                ->selectRaw('date, session, SUM(liters) as liters')
                ->groupBy('date', 'session')
                ->orderByDesc('date')
                ->get()
                ->groupBy(fn ($row) => substr((string) $row->date, 0, 10))
                ->map(fn ($rows, $date) => [
                    'date' => $date,
                    'morning' => (float) ($rows->firstWhere('session', 'morning')->liters ?? 0),
                    'evening' => (float) ($rows->firstWhere('session', 'evening')->liters ?? 0),
                ])
                ->values(),
            'month' => (float) MilkRecord::query()->whereDate('date', '>=', now()->startOfMonth())->sum('liters'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.now()->subDays(7)->toDateString()],
            'session' => ['required', Rule::in(array_keys(MilkRecord::SESSIONS))],
            'rows' => ['required', 'array', 'min:1', 'max:100'],
            'rows.*.animal_id' => ['nullable', 'exists:animals,id'],
            'rows.*.liters' => ['nullable', 'numeric', 'min:0', 'max:80'],
        ], [
            'date.after_or_equal' => 'এক সপ্তাহের বেশি পুরনো দুধের হিসাব অ্যাডমিন ঠিক করবেন।',
        ]);

        $gavi = CostCenter::query()->where('code', 'GAVI')->value('id');
        $saved = 0;

        foreach ($data['rows'] as $row) {
            if ((float) ($row['liters'] ?? 0) <= 0) {
                continue;
            }

            MilkRecord::query()->updateOrCreate(
                ['date' => $data['date'], 'session' => $data['session'], 'animal_id' => $row['animal_id'] ?? null],
                ['liters' => $row['liters'], 'cost_center_id' => $gavi, 'recorded_by' => $request->user()->id],
            );
            $saved++;
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $saved > 0 ? 'দুধের হিসাব রাখা হয়েছে।' : 'কোনো লিটার দেওয়া হয়নি।']);

        return back();
    }
}
