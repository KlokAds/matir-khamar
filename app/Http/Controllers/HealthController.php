<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\HealthEvent;
use App\Services\HerdCare;
use App\Support\Lookups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Vaccines, deworming and treatments, and what is due next. */
class HealthController extends Controller
{
    public function index(HerdCare $care): Response
    {
        return Inertia::render('health/Index', [
            'due' => $care->healthDue(now()->startOfDay(), 30),
            'history' => HealthEvent::query()->with(['animal:id,tag,name', 'costCenter:id,name'])
                ->orderByDesc('given_on')->orderByDesc('id')->limit(100)->get()
                ->map(fn (HealthEvent $e) => [
                    'id' => $e->id,
                    'kind' => $e->kind,
                    'kind_label' => HealthEvent::KINDS[$e->kind] ?? $e->kind,
                    'name' => $e->name,
                    'target' => $e->target(),
                    'animal_id' => $e->animal_id,
                    'given_on' => $e->given_on->toDateString(),
                    'next_due_on' => $e->next_due_on?->toDateString(),
                    'dose' => $e->dose,
                    'by_whom' => $e->by_whom,
                    'notes' => $e->notes,
                    'deletable' => (bool) $e->created_at?->greaterThan(now()->subDay()),
                ]),
            'kinds' => HealthEvent::KINDS,
            'presets' => HealthEvent::PRESETS,
            'centers' => array_values(array_filter(Lookups::centers(), fn ($c) => $c['holds_animals'])),
            'animals' => Animal::query()->where('status', 'active')->orderBy('tag')->get(['id', 'tag', 'name', 'cost_center_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', Rule::in(['animals', 'center', 'farm'])],
            'animal_ids' => ['required_if:target,animals', 'array', 'max:200'],
            'animal_ids.*' => ['integer', 'exists:animals,id'],
            'cost_center_id' => ['required_if:target,center', 'nullable', 'exists:cost_centers,id'],
            'kind' => ['required', Rule::in(array_keys(HealthEvent::KINDS))],
            'name' => ['required', 'string', 'max:120'],
            'given_on' => ['required', 'date', 'before_or_equal:today'],
            'next_due_on' => ['nullable', 'date', 'after:given_on'],
            'dose' => ['nullable', 'string', 'max:120'],
            'by_whom' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $common = [
            ...Arr::only($data, ['kind', 'name', 'given_on', 'next_due_on', 'dose', 'by_whom', 'notes']),
            'created_by' => $request->user()->id,
        ];

        $count = DB::transaction(function () use ($data, $common): int {
            if ($data['target'] === 'animals') {
                foreach (array_unique($data['animal_ids']) as $id) {
                    HealthEvent::query()->create([...$common, 'animal_id' => (int) $id]);
                }

                return count(array_unique($data['animal_ids']));
            }

            HealthEvent::query()->create([...$common, 'cost_center_id' => $data['target'] === 'center' ? $data['cost_center_id'] : null]);

            return 1;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$data['name']} লেখা হলো".($count > 1 ? " ({$count}টি গরু)" : '').'।']);

        return back();
    }

    public function destroy(HealthEvent $event): RedirectResponse
    {
        abort_unless($event->created_at?->greaterThan(now()->subDay()), 403, 'একদিনের বেশি পুরনো লেখা মোছা যায় না।');
        $event->delete();

        return back();
    }
}
