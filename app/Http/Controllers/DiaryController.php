<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\FarmNote;
use App\Models\Goal;
use App\Support\CareGuide;
use App\Support\Lookups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The farm diary (what happened, mistakes, lessons, ideas), the goals the farm is chasing,
 * and the routine to keep — the part of running a farm that is not in the ledger.
 */
class DiaryController extends Controller
{
    public function index(Request $request): Response
    {
        $kind = array_key_exists($request->string('kind')->toString(), FarmNote::KINDS) ? $request->string('kind')->toString() : null;

        return Inertia::render('diary/Index', [
            'kind' => $kind,
            'kinds' => FarmNote::KINDS,
            'counts' => FarmNote::query()->selectRaw('kind, COUNT(*) as n')->groupBy('kind')->pluck('n', 'kind'),
            'notes' => FarmNote::query()->with(['costCenter:id,name', 'animal:id,tag', 'author:id,name'])
                ->when($kind, fn ($query) => $query->where('kind', $kind))
                ->orderByDesc('date')->orderByDesc('id')->limit(200)->get()
                ->map(fn (FarmNote $note) => [
                    'id' => $note->id,
                    'date' => $note->date->toDateString(),
                    'kind' => $note->kind,
                    'title' => $note->title,
                    'body' => $note->body,
                    'center' => $note->costCenter?->name,
                    'animal' => $note->animal?->tag,
                    'animal_id' => $note->animal_id,
                    'follow_up_on' => $note->follow_up_on?->toDateString(),
                    'done' => $note->done_at !== null,
                    'author' => $note->author?->name,
                ]),
            'goals' => Goal::query()->with('costCenter:id,name')->orderByRaw('achieved_at IS NOT NULL')->orderBy('due_on')->get()
                ->map(fn (Goal $goal) => [
                    ...$goal->only(['id', 'title', 'unit', 'start_value', 'target_value', 'current_value', 'notes', 'cost_center_id']),
                    'due_on' => $goal->due_on?->toDateString(),
                    'center' => $goal->costCenter?->name,
                    'achieved' => $goal->achieved_at !== null,
                    'progress' => $goal->progress(),
                ]),
            'centers' => Lookups::centers(),
            'animals' => Animal::query()->where('status', 'active')->orderBy('tag')->get(['id', 'tag', 'name']),
            'routine' => CareGuide::routine(),
        ]);
    }

    public function storeNote(Request $request): RedirectResponse
    {
        FarmNote::query()->create([...$this->noteData($request), 'created_by' => $request->user()->id]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'ডায়েরিতে লেখা হলো।']);

        return back();
    }

    public function updateNote(Request $request, FarmNote $note): RedirectResponse
    {
        if ($request->has('done')) {
            $note->update(['done_at' => $request->boolean('done') ? now() : null]);

            return back();
        }

        $note->update($this->noteData($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'নোট হালনাগাদ হলো।']);

        return back();
    }

    public function storeGoal(Request $request): RedirectResponse
    {
        $data = $this->goalData($request);
        $data['current_value'] ??= $data['start_value'];
        Goal::query()->create($this->markAchieved($data));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'নতুন লক্ষ্য রাখা হলো।']);

        return back();
    }

    public function updateGoal(Request $request, Goal $goal): RedirectResponse
    {
        $goal->update($this->markAchieved($this->goalData($request), $goal));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'লক্ষ্য হালনাগাদ হলো।']);

        return back();
    }

    /** @return array<string, mixed> */
    private function noteData(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'kind' => ['required', Rule::in(array_keys(FarmNote::KINDS))],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:5000'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'follow_up_on' => ['nullable', 'date'],
        ]);
    }

    /** @return array<string, mixed> */
    private function goalData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'unit' => ['nullable', 'string', 'max:30'],
            'start_value' => ['required', 'numeric', 'min:-99999999', 'max:99999999'],
            'target_value' => ['required', 'numeric', 'min:-99999999', 'max:99999999'],
            'current_value' => ['nullable', 'numeric', 'min:-99999999', 'max:99999999'],
            'due_on' => ['nullable', 'date'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function markAchieved(array $data, ?Goal $goal = null): array
    {
        $start = (float) $data['start_value'];
        $target = (float) $data['target_value'];
        $current = (float) ($data['current_value'] ?? $start);
        $reached = $target >= $start ? $current >= $target : $current <= $target;

        $data['achieved_at'] = $reached ? ($goal->achieved_at ?? now()) : null;

        return $data;
    }
}
