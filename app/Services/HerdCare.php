<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\Batch;
use App\Models\Breeding;
use App\Models\FarmNote;
use App\Support\BanglaDate;
use App\Support\CareGuide;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

/**
 * The herd beyond money: services and pregnancies, fattening rounds, weights, and the
 * reminders that come out of them.
 */
class HerdCare
{
    public function __construct(private ReportService $reports, private AllocationService $allocation) {}

    /**
     * Record a service. When only "how many months along" is known (a cow bought already
     * carrying), the service day is worked back from it and marked as an estimate.
     *
     * @param  array{served_on?:string|null, months_pregnant?:float|int|string|null, method?:string|null, sire?:string|null, by_whom?:string|null, notes?:string|null}  $data
     */
    public function recordService(Animal $animal, array $data): Breeding
    {
        if ($animal->sex !== 'female') {
            throw ValidationException::withMessages(['animal' => 'শুধু গাভী বা বকনার গর্ভের হিসাব রাখা যায়।']);
        }

        if (Breeding::query()->where('animal_id', $animal->id)->whereNull('calved_on')->where('result', '!=', 'empty')->exists()) {
            throw ValidationException::withMessages(['served_on' => 'এই গরুর আগের গর্ভের হিসাব এখনো খোলা — আগে সেটা শেষ করুন।']);
        }

        $months = (float) ($data['months_pregnant'] ?? 0);
        $estimated = $months > 0;
        if (! $estimated && empty($data['served_on'])) {
            throw ValidationException::withMessages(['served_on' => 'বীজ দেওয়ার তারিখ, নয়তো কত মাস চলছে — একটি দিন।']);
        }

        $served = $estimated
            ? now()->startOfDay()->subDays((int) round($months * 30.4))
            : Date::parse((string) ($data['served_on'] ?? ''));

        return Breeding::query()->create([
            'animal_id' => $animal->id,
            'served_on' => $served->toDateString(),
            'served_on_estimated' => $estimated,
            'method' => $data['method'] ?? 'ai',
            'sire' => $data['sire'] ?? null,
            'by_whom' => $data['by_whom'] ?? null,
            // Bought already carrying: the pregnancy is known, not waiting for a check.
            'result' => $estimated ? 'pregnant' : 'pending',
            'expected_on' => $served->addDays(Breeding::GESTATION_DAYS)->toDateString(),
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /** @return array<string, mixed> */
    public function breedingCard(Breeding $breeding): array
    {
        $day = $breeding->daysCarried();
        $open = $breeding->isOpen();
        $stage = $open ? CareGuide::stageFor(CareGuide::pregnancy(), $day) : null;

        return [
            'id' => $breeding->id,
            'animal_id' => $breeding->animal_id,
            'tag' => $breeding->animal->tag ?? null,
            'name' => $breeding->animal->name ?? null,
            'served_on' => $breeding->served_on->toDateString(),
            'estimated' => $breeding->served_on_estimated,
            'method' => Breeding::METHODS[$breeding->method] ?? $breeding->method,
            'sire' => $breeding->sire,
            'by_whom' => $breeding->by_whom,
            'result' => $breeding->result,
            'result_label' => Breeding::RESULTS[$breeding->result] ?? $breeding->result,
            'checked_on' => $breeding->checked_on?->toDateString(),
            'expected_on' => $breeding->expected_on->toDateString(),
            'calved_on' => $breeding->calved_on?->toDateString(),
            'outcome' => $breeding->outcome,
            'outcome_label' => $breeding->outcome ? (Breeding::OUTCOMES[$breeding->outcome] ?? $breeding->outcome) : null,
            'calf_id' => $breeding->calf_id,
            'notes' => $breeding->notes,
            'open' => $open,
            'day' => $day,
            'months' => BanglaDate::span($day),
            'days_left' => $open ? (int) now()->startOfDay()->diffInDays($breeding->expected_on, false) : null,
            'progress' => min(100, (int) round($day / Breeding::GESTATION_DAYS * 100)),
            'stage' => $stage,
        ];
    }

    /** @return array<string, mixed> */
    public function batchCard(Batch $batch): array
    {
        $batch->loadMissing(['animals.placements', 'animals.weights', 'costCenter:id,name']);
        $days = $batch->daysRunning();
        $cost = ['purchase' => 0, 'direct' => 0, 'shared' => 0, 'sale' => 0];
        $gains = [];
        $animals = [];

        foreach ($batch->animals as $animal) {
            $c = $this->reports->animalCost($animal, $this->allocation);
            $cost['purchase'] += $c['purchase'];
            $cost['direct'] += $c['direct'];
            $cost['shared'] += $c['shared'];
            $cost['sale'] += (int) $c['sale'];
            $growth = $this->growth($animal);

            if ($growth['per_day'] !== null) {
                $gains[] = $growth['per_day'];
            }

            $animals[] = [
                'id' => $animal->id,
                'tag' => $animal->tag,
                'name' => $animal->name,
                'status' => $animal->status,
                'purchase_cost' => $animal->purchase_cost,
                ...$growth,
            ];
        }

        $spent = $cost['purchase'] + $cost['direct'] + $cost['shared'];
        $soldAll = $batch->animals->isNotEmpty() && $batch->animals->every(fn (Animal $a) => $a->status !== 'active');

        return [
            'id' => $batch->id,
            'label' => $batch->label(),
            'name' => $batch->name,
            'number' => $batch->number,
            'center' => $batch->costCenter->name,
            'cost_center_id' => $batch->cost_center_id,
            'started_on' => $batch->started_on->toDateString(),
            'closed_on' => $batch->closed_on?->toDateString(),
            'days' => $days,
            'span' => BanglaDate::span($days),
            'target_days' => $batch->target_days,
            'target_on' => $batch->started_on->addDays($batch->target_days)->toDateString(),
            'target_gain_kg' => $batch->target_gain_kg,
            'progress' => min(100, (int) round($days / max(1, $batch->target_days) * 100)),
            'notes' => $batch->notes,
            'animals' => $animals,
            'head' => count($animals),
            'avg_gain' => $gains === [] ? null : round(array_sum($gains) / count($gains), 2),
            'cost' => $cost,
            'spent' => $spent,
            'profit' => $soldAll ? $cost['sale'] - $spent : null,
            'stage' => $batch->closed_on ? null : CareGuide::stageFor(CareGuide::fattening(), $days),
        ];
    }

    /**
     * First and last weight and the gain per day between them.
     *
     * @return array{first_kg:?float, last_kg:?float, last_on:?string, per_day:?float}
     */
    public function growth(Animal $animal): array
    {
        /** @var AnimalWeight|null $first */
        $first = $animal->weights->first();
        /** @var AnimalWeight|null $last */
        $last = $animal->weights->last();
        $perDay = null;

        if ($first !== null && $last !== null) {
            $days = (int) $first->date->diffInDays($last->date);
            $perDay = $days > 0 ? round(($last->kg - $first->kg) / $days, 2) : null;
        }

        return [
            'first_kg' => $first?->kg,
            'last_kg' => $last?->kg,
            'last_on' => $last?->date->toDateString(),
            'per_day' => $perDay,
        ];
    }

    /** Whether the farm has cows in the dairy category, so missing milk is worth a nudge. */
    public function hasMilkingCows(): bool
    {
        return Animal::query()->where('status', 'active')
            ->whereHas('costCenter', fn ($query) => $query->where('code', 'GAVI'))
            ->exists();
    }

    /**
     * What needs doing soon, newest concern first: heat checks, pregnancy checks, calvings,
     * fattening rounds past their target, and diary follow-ups.
     *
     * @return list<array{kind:string, title:string, detail:string, href:string, when:string, urgent:bool}>
     */
    public function reminders(?CarbonInterface $today = null): array
    {
        $today = ($today ?? now())->startOfDay();
        $items = [];

        $open = Breeding::query()->with('animal:id,tag,name,status')
            ->whereNull('calved_on')->where('result', '!=', 'empty')
            ->whereHas('animal', fn ($query) => $query->where('status', 'active'))
            ->get();

        foreach ($open as $breeding) {
            $day = (int) $breeding->served_on->diffInDays($today);
            $tag = $breeding->animal->tag;
            $href = "/animals/{$breeding->animal_id}";

            if ($breeding->result === 'pending' && $day >= 18 && $day <= 24) {
                $items[] = ['kind' => 'heat', 'title' => "{$tag}: আবার গরম আসছে কি না দেখুন", 'detail' => "বীজ দেওয়ার {$day} দিন", 'href' => $href, 'when' => $today->toDateString(), 'urgent' => false];
            }

            if ($breeding->result === 'pending' && $day >= 60) {
                $items[] = ['kind' => 'check', 'title' => "{$tag}: গর্ভ পরীক্ষা করান", 'detail' => "বীজ দেওয়ার {$day} দিন হলো, ফল লেখা হয়নি", 'href' => $href, 'when' => $today->toDateString(), 'urgent' => $day > 90];
            }

            $left = (int) $today->diffInDays($breeding->expected_on, false);

            if ($left <= 30) {
                $items[] = ['kind' => 'calving', 'title' => $left >= 0 ? "{$tag}: {$left} দিনের মধ্যে বাচ্চা হবে" : "{$tag}: বাচ্চা হওয়ার দিন ".abs($left).' দিন পার', 'detail' => 'আনুমানিক '.BanglaDate::day($breeding->expected_on), 'href' => $href, 'when' => $breeding->expected_on->toDateString(), 'urgent' => $left <= 7];
            }

            if ($breeding->result === 'pregnant' && $day >= 218 && $day <= 230) {
                $items[] = ['kind' => 'dry', 'title' => "{$tag}: দুধ বন্ধ করার সময় (শুষ্ক সময়)", 'detail' => "গর্ভের {$day} দিন", 'href' => $href, 'when' => $today->toDateString(), 'urgent' => false];
            }
        }

        foreach (Batch::query()->whereNull('closed_on')->get() as $batch) {
            $days = $batch->daysRunning();

            if ($days >= $batch->target_days) {
                $items[] = ['kind' => 'batch', 'title' => "{$batch->label()}: লক্ষ্যের {$batch->target_days} দিন পূর্ণ", 'detail' => "চলছে {$days} দিন — বিক্রির কথা ভাবুন", 'href' => '/batches', 'when' => $today->toDateString(), 'urgent' => $days > $batch->target_days + 14];
            }
        }

        $notes = FarmNote::query()->whereNull('done_at')->whereNotNull('follow_up_on')
            ->whereDate('follow_up_on', '<=', $today->addDays(3))->orderBy('follow_up_on')->get();

        foreach ($notes as $note) {
            $items[] = ['kind' => 'note', 'title' => $note->title, 'detail' => 'ডায়েরির কাজ · '.BanglaDate::day($note->follow_up_on), 'href' => '/diary', 'when' => $note->follow_up_on->toDateString(), 'urgent' => $note->follow_up_on->lessThan($today)];
        }

        usort($items, fn ($a, $b) => [$b['urgent'], $a['when']] <=> [$a['urgent'], $b['when']]);

        return $items;
    }
}
