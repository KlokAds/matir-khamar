<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\Batch;
use App\Models\Breeding;
use App\Models\CostCenter;
use App\Services\HerdCare;
use App\Support\CareGuide;
use App\Support\Lookups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pregnancies and calvings, fattening batches and weights: the herd's own story next to
 * its money.
 */
class HerdController extends Controller
{
    public function breeding(HerdCare $care): Response
    {
        $breedings = Breeding::query()->with('animal:id,tag,name,status,cost_center_id')
            ->whereHas('animal', fn ($query) => $query->where('status', 'active'))
            ->orderBy('expected_on')
            ->get();

        $open = $breedings->filter(fn (Breeding $b) => $b->isOpen());
        $openIds = $open->pluck('animal_id')->all();

        return Inertia::render('herd/Breeding', [
            'open' => $open->map(fn (Breeding $b) => $care->breedingCard($b))->values(),
            'recent' => Breeding::query()->with('animal:id,tag,name')->whereNotNull('calved_on')
                ->orderByDesc('calved_on')->limit(10)->get()->map(fn (Breeding $b) => $care->breedingCard($b)),
            // Grown females with nothing open: ready to be watched for heat and served.
            'waiting' => Animal::query()->with('costCenter:id,name')->where('status', 'active')->where('sex', 'female')
                ->whereNotIn('id', $openIds)
                ->whereHas('costCenter', fn ($query) => $query->whereIn('code', ['GAVI', 'BOKNA']))
                ->orderBy('tag')->get()
                ->map(fn (Animal $a) => [
                    'id' => $a->id,
                    'tag' => $a->tag,
                    'name' => $a->name,
                    'center' => $a->costCenter->name,
                    'last_calved' => Breeding::query()->where('animal_id', $a->id)->max('calved_on'),
                ]),
            'guide' => CareGuide::pregnancy(),
            'calfGuide' => CareGuide::calf(),
        ]);
    }

    public function storeBreeding(Request $request, Animal $animal, HerdCare $care): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['served', 'months'])],
            'served_on' => ['required_if:mode,served', 'nullable', 'date', 'before_or_equal:today'],
            'months_pregnant' => ['required_if:mode,months', 'nullable', 'numeric', 'min:0.5', 'max:9.5'],
            'method' => ['required', Rule::in(array_keys(Breeding::METHODS))],
            'sire' => ['nullable', 'string', 'max:120'],
            'by_whom' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['mode'] === 'served') {
            unset($data['months_pregnant']);
        }

        $care->recordService($animal, $data);
        Inertia::flash('toast', ['type' => 'success', 'message' => __(':tag: গর্ভের হিসাব শুরু হলো।', ['tag' => $animal->tag])]);

        return back();
    }

    public function updateBreeding(Request $request, Breeding $breeding): RedirectResponse
    {
        $data = $request->validate([
            'result' => ['sometimes', Rule::in(array_keys(Breeding::RESULTS))],
            'checked_on' => ['nullable', 'date', 'before_or_equal:today'],
            'calved_on' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:'.$breeding->served_on->toDateString()],
            'outcome' => ['required_with:calved_on', 'nullable', Rule::in(array_keys(Breeding::OUTCOMES))],
            'sire' => ['sometimes', 'nullable', 'string', 'max:120'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        if (! empty($data['calved_on'])) {
            $data['result'] = 'pregnant';
        }

        $breeding->update($data);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('গর্ভের হিসাব হালনাগাদ হলো।')]);

        return back();
    }

    public function batches(HerdCare $care): Response
    {
        $batches = Batch::query()->orderByRaw('closed_on IS NOT NULL')->orderByDesc('started_on')->get();

        return Inertia::render('herd/Batches', [
            'batches' => $batches->map(fn (Batch $b) => $care->batchCard($b)),
            'centers' => array_values(array_filter(Lookups::centers(), fn ($c) => $c['holds_animals'])),
            'defaultCenterId' => CostCenter::query()->where('code', 'MOTATAJA')->value('id'),
            'guide' => CareGuide::fattening(),
        ]);
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $data = $this->batchData($request);
        $data['number'] = Batch::nextNumber((int) $data['cost_center_id']);
        $batch = Batch::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':batch খোলা হলো।', ['batch' => $batch->label()])]);

        return back();
    }

    public function updateBatch(Request $request, Batch $batch): RedirectResponse
    {
        $data = $this->batchData($request, $batch);
        unset($data['cost_center_id']);
        $batch->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':batch হালনাগাদ হলো।', ['batch' => $batch->label()])]);

        return back();
    }

    public function storeWeight(Request $request, Animal $animal): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'kg' => ['required', 'numeric', 'min:5', 'max:2000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        AnimalWeight::query()->create([...$data, 'animal_id' => $animal->id, 'created_by' => $request->user()->id]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __(':tag: ওজন লেখা হলো।', ['tag' => $animal->tag])]);

        return back();
    }

    /** @return array<string, mixed> */
    private function batchData(Request $request, ?Batch $batch = null): array
    {
        return $request->validate([
            'cost_center_id' => [$batch ? 'nullable' : 'required', Rule::exists('cost_centers', 'id')->where('holds_animals', true)],
            'name' => ['nullable', 'string', 'max:60'],
            'started_on' => ['required', 'date', 'before_or_equal:today'],
            'target_days' => ['required', 'integer', 'min:7', 'max:730'],
            'target_gain_kg' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'closed_on' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:started_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
