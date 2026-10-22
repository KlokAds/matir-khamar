<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Batch;
use App\Models\MarketPrice;
use App\Models\Setting;
use App\Support\BanglaDate;
use App\Support\Tr;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * কোরবানি পরিকল্পনা: fattening rounds planned around Eid-ul-Adha — when to buy, how long to
 * feed, and what the batches now on feed should weigh and fetch on the day.
 */
class KurbaniPlanner
{
    /** Approximate Eid-ul-Adha dates in Bangladesh; the moon decides, so the farm can correct it. */
    public const EID_DATES = ['2027-05-17', '2028-05-05', '2029-04-24', '2030-04-13'];

    public const EID_KEY = 'kurbani.eid_date';

    public const PRICE_KEY = 'kurbani.price_per_kg';

    /** ৳500 per kg live weight, in poisha. */
    public const DEFAULT_PRICE_PER_KG = 50000;

    /** Daily gain assumed when an animal has not been weighed twice, kg. */
    public const ASSUMED_GAIN = 0.7;

    /** Feed and care per head per day when the batch has no cost history yet, poisha (৳180). */
    public const DEFAULT_FEED_PER_DAY = 18000;

    /** Buying window: from 120 to 90 days before Eid; 60 days is the late option. */
    public const BUY_FROM = 120;

    public const BUY_TO = 90;

    public const BUY_LATE = 60;

    public function __construct(
        private HerdCare $care,
        private ReportService $reports,
        private AllocationService $allocation,
    ) {}

    /**
     * The Eid date in use: a saved one that has not passed, else the next known date after today.
     *
     * @return array{date: CarbonImmutable, saved: bool}
     */
    public function eid(CarbonInterface $today): array
    {
        $today = CarbonImmutable::parse($today->toDateString());
        $saved = Setting::get(self::EID_KEY);

        if ($saved !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $saved) === 1) {
            $date = CarbonImmutable::parse($saved);

            if ($date->greaterThanOrEqualTo($today)) {
                return ['date' => $date, 'saved' => true];
            }
        }

        foreach (self::EID_DATES as $known) {
            $date = CarbonImmutable::parse($known);

            if ($date->greaterThan($today)) {
                return ['date' => $date, 'saved' => false];
            }
        }

        // Past the table: Eid moves about 11 days earlier each year.
        $date = CarbonImmutable::parse(self::EID_DATES[count(self::EID_DATES) - 1]);

        while ($date->lessThanOrEqualTo($today)) {
            $date = $date->addDays(354);
        }

        return ['date' => $date, 'saved' => false];
    }

    /** Live-weight sale price per kg in poisha. */
    public function pricePerKg(): int
    {
        $saved = (int) Setting::get(self::PRICE_KEY, '0');

        return $saved > 0 ? $saved : self::DEFAULT_PRICE_PER_KG;
    }

    /**
     * Everything the page needs.
     *
     * @return array<string, mixed>
     */
    public function plan(?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse(($today ?? now())->toDateString());
        ['date' => $eid, 'saved' => $saved] = $this->eid($today);
        $daysLeft = (int) $today->diffInDays($eid, false);
        $haat = MarketPrice::query()->where('commodity', 'গরু — মোটাতাজা')->where('unit', 'head')
            ->orderByDesc('date')->orderByDesc('id')->first();
        $pricePerKg = $this->pricePerKg();

        $batches = Batch::query()
            ->whereNull('closed_on')
            ->whereHas('costCenter', fn ($q) => $q->where('code', 'MOTATAJA')
                ->orWhereHas('parent', fn ($p) => $p->where('code', 'MOTATAJA')))
            ->with(['animals' => fn ($q) => $q->where('status', 'active')->orderBy('tag'), 'animals.weights', 'animals.placements'])
            ->orderBy('started_on')
            ->get();

        return [
            'eid' => $eid->toDateString(),
            'eid_saved' => $saved,
            'known_dates' => self::EID_DATES,
            'today' => $today->toDateString(),
            'days_left' => $daysLeft,
            'window' => [
                'from' => $eid->subDays(self::BUY_FROM)->toDateString(),
                'to' => $eid->subDays(self::BUY_TO)->toDateString(),
                'late' => $eid->subDays(self::BUY_LATE)->toDateString(),
            ],
            'phase' => $this->phase($daysLeft),
            'price_per_kg' => $pricePerKg,
            'price_saved' => Setting::get(self::PRICE_KEY) !== null,
            'haat' => $haat ? ['price' => $haat->price, 'date' => $haat->date->toDateString(), 'place' => $haat->place] : null,
            'assumed_gain' => self::ASSUMED_GAIN,
            'batches' => $batches->map(fn (Batch $b) => $this->project($b, $eid, $today, $pricePerKg, $haat?->price))->values()->all(),
        ];
    }

    /**
     * Where today stands against Eid: before the window, buying, late buying, feeding, last month.
     */
    public function phase(int $daysLeft): string
    {
        return match (true) {
            $daysLeft > self::BUY_FROM => 'early',
            $daysLeft >= self::BUY_TO => 'buy',
            $daysLeft >= self::BUY_LATE => 'late',
            $daysLeft > 30 => 'feed',
            default => 'sell',
        };
    }

    /**
     * One open batch carried forward to Eid.
     *
     * @return array<string, mixed>
     */
    public function project(Batch $batch, CarbonImmutable $eid, CarbonImmutable $today, int $pricePerKg, ?int $headPrice = null): array
    {
        $daysLeft = max(0, (int) $today->diffInDays($eid, false));
        $daysRun = $batch->daysRunning();
        $daysByEid = (int) $batch->started_on->diffInDays($eid) + 1;

        $costs = [];
        $growths = [];
        $gains = [];

        foreach ($batch->animals as $animal) {
            $c = $this->reports->animalCost($animal, $this->allocation);
            $costs[$animal->id] = $c;
            $growths[$animal->id] = $g = $this->care->growth($animal);

            if ($g['per_day'] !== null && $g['per_day'] > 0) {
                $gains[] = $g['per_day'];
            }
        }

        $head = $batch->animals->count();
        $running = array_sum(array_map(fn (array $c) => $c['direct'] + $c['shared'], $costs));
        $feedOwn = $head > 0 && $daysRun >= 7 && $running > 0;
        $feedPerDay = $feedOwn ? (int) round($running / $head / $daysRun) : self::DEFAULT_FEED_PER_DAY;
        $batchGain = $gains === [] ? null : round(array_sum($gains) / count($gains), 2);

        $animals = [];
        $totals = ['spent' => 0, 'future' => 0, 'value' => 0, 'value_head' => 0, 'weighed' => 0, 'kg' => 0.0];

        foreach ($batch->animals as $animal) {
            $animals[] = $row = $this->animalRow($animal, $costs[$animal->id], $growths[$animal->id], $batchGain, $eid, $daysLeft, $feedPerDay, $pricePerKg, $headPrice);
            $totals['spent'] += $row['spent'];
            $totals['future'] += $row['future_cost'];

            if ($row['expected_kg'] !== null) {
                $totals['weighed']++;
                $totals['kg'] += $row['expected_kg'];
                $totals['value'] += (int) $row['value'];
            }

            $totals['value_head'] += $headPrice ?? 0;
        }

        $weighedCost = array_sum(array_map(fn (array $r) => $r['expected_kg'] !== null ? $r['spent'] + $r['future_cost'] : 0, $animals));
        $ready = $daysByEid >= $batch->target_days;

        return [
            'id' => $batch->id,
            'label' => $batch->label(),
            'number' => $batch->number,
            'started_on' => $batch->started_on->toDateString(),
            'target_days' => $batch->target_days,
            'target_on' => $batch->started_on->addDays($batch->target_days)->toDateString(),
            'days' => $daysRun,
            'days_by_eid' => $daysByEid,
            'days_left' => $daysLeft,
            'ready' => $ready,
            'short_days' => $ready ? 0 : $batch->target_days - $daysByEid,
            'head' => $head,
            'avg_gain' => $batchGain,
            'feed_per_day' => $feedPerDay,
            'feed_own' => $feedOwn,
            'animals' => $animals,
            'weighed' => $totals['weighed'],
            'expected_kg' => round($totals['kg'], 1),
            'spent' => $totals['spent'],
            'future_cost' => $totals['future'],
            'cost_total' => $totals['spent'] + $totals['future'],
            'value' => $totals['value'],
            'profit' => $totals['weighed'] > 0 ? $totals['value'] - $weighedCost : null,
            'value_head' => $headPrice !== null ? $totals['value_head'] : null,
            'profit_head' => $headPrice !== null ? $totals['value_head'] - $totals['spent'] - $totals['future'] : null,
        ];
    }

    /**
     * What should come into the reminder list: the buying window, and the last month before Eid.
     *
     * @return list<array{kind:string, title:string, detail:string, href:string, when:string, urgent:bool}>
     */
    public function reminders(CarbonInterface $today): array
    {
        $today = CarbonImmutable::parse($today->toDateString());
        $eid = $this->eid($today)['date'];
        $left = (int) $today->diffInDays($eid, false);
        $items = [];

        if ($left <= self::BUY_FROM && $left >= self::BUY_TO) {
            $items[] = [
                'kind' => 'kurbani',
                'title' => Tr::get('কোরবানির জন্য গরু কেনার সময় শুরু'),
                'detail' => Tr::get('ঈদ :date — :from থেকে :to এর মধ্যে কিনলে ৯০–১২০ দিন খাওয়ানো যাবে', [
                    'date' => BanglaDate::day($eid),
                    'from' => BanglaDate::day($eid->subDays(self::BUY_FROM)),
                    'to' => BanglaDate::day($eid->subDays(self::BUY_TO)),
                ]),
                'href' => '/kurbani',
                'when' => $today->toDateString(),
                'urgent' => $left <= self::BUY_TO + 7,
            ];
        }

        if ($left >= 0 && $left <= 30) {
            $items[] = [
                'kind' => 'kurbani',
                'title' => Tr::get('কোরবানির :n দিন বাকি — ব্যাচ বিক্রির প্রস্তুতি', ['n' => $left]),
                'detail' => Tr::get('ঈদ :date — ওজন মাপুন, হাট ও পরিবহন ঠিক করুন', ['date' => BanglaDate::day($eid)]),
                'href' => '/kurbani',
                'when' => $today->toDateString(),
                'urgent' => $left <= 10,
            ];
        }

        return $items;
    }

    /**
     * @param  array{purchase:int, direct:int, shared:int, sale:int|float}|array<string, int|float>  $cost
     * @param  array{first_kg:?float, last_kg:?float, last_on:?string, per_day:?float}  $growth
     * @return array<string, mixed>
     */
    private function animalRow(Animal $animal, array $cost, array $growth, ?float $batchGain, CarbonImmutable $eid, int $daysLeft, int $feedPerDay, int $pricePerKg, ?int $headPrice): array
    {
        $spent = (int) ($cost['purchase'] + $cost['direct'] + $cost['shared']);
        $future = $feedPerDay * $daysLeft;

        $own = $growth['per_day'] !== null && $growth['per_day'] > 0;
        $gain = $own ? (float) $growth['per_day'] : ($batchGain ?? self::ASSUMED_GAIN);
        $gainSource = $own ? 'own' : ($batchGain !== null ? 'batch' : 'assumed');

        $expected = null;
        $value = null;

        if ($growth['last_kg'] !== null && $growth['last_on'] !== null) {
            $sinceWeighed = max(0, (int) CarbonImmutable::parse($growth['last_on'])->diffInDays($eid, false));
            $expected = round($growth['last_kg'] + $gain * $sinceWeighed, 1);
            $value = (int) round($expected * $pricePerKg);
        }

        return [
            'id' => $animal->id,
            'tag' => $animal->tag,
            'name' => $animal->name,
            'last_kg' => $growth['last_kg'],
            'last_on' => $growth['last_on'],
            'gain' => round($gain, 2),
            'gain_source' => $gainSource,
            'expected_kg' => $expected,
            'value' => $value,
            'spent' => $spent,
            'future_cost' => $future,
            'profit' => $value !== null ? $value - $spent - $future : null,
            'profit_head' => $headPrice !== null ? $headPrice - $spent - $future : null,
        ];
    }
}
