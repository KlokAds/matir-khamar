<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\CostCenter;
use App\Models\MilkRecord;
use App\Models\Reminder;
use App\Models\User;
use App\Support\Tr;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Today's jobs for whoever does the daily work: milk morning and evening, the feed given, doses
 * due, animals to weigh, and reminders for today. Each ticks itself off when the app sees it done.
 */
class DailyChecklist
{
    /** A fattening animal is weighed at least this often. */
    public const WEIGH_EVERY_DAYS = 30;

    public function __construct(private HerdCare $care) {}

    /**
     * @return list<array{key:string, title:string, detail:string, href:string, done:bool}>
     */
    public function for(User $user, ?CarbonInterface $today = null): array
    {
        $today = ($today ?? now())->startOfDay();
        $date = $today->toDateString();
        $modules = CostCenter::modules();
        $items = [];

        if ($modules['milk']) {
            foreach (['morning' => 'সকালের দুধ লিখুন', 'evening' => 'বিকালের দুধ লিখুন'] as $session => $title) {
                $liters = (float) MilkRecord::query()->whereDate('date', $date)->where('session', $session)->sum('liters');
                $items[] = [
                    'key' => "milk-{$session}",
                    'title' => Tr::get($title),
                    'detail' => $liters > 0 ? Tr::get(':n লিটার লেখা হয়েছে', ['n' => round($liters, 1)]) : Tr::get('এখনো লেখা হয়নি'),
                    'href' => '/milk',
                    'done' => $liters > 0,
                ];
            }
        }

        if ($modules['cattle'] || $modules['goat']) {
            $fed = DB::table('stock_movements')->whereDate('date', $date)->where('direction', 'out')->whereNotNull('cost_center_id')->count();
            $items[] = [
                'key' => 'feed',
                'title' => Tr::get('আজ কী খাবার দেওয়া হলো, লিখুন'),
                'detail' => $fed > 0 ? Tr::get(':nটি লেখা হয়েছে', ['n' => $fed]) : Tr::get('খড়, ঘাস, দানাদার — যা দেওয়া হলো'),
                'href' => '/stock/move?mode=issue',
                'done' => $fed > 0,
            ];

            foreach ($this->care->healthDue($today, 0) as $index => $due) {
                $items[] = [
                    'key' => "health-{$index}",
                    'title' => $due['title'],
                    'detail' => $due['detail'],
                    'href' => $due['href'],
                    'done' => false,
                ];
            }

            $toWeigh = $this->toWeigh($today);

            if ($toWeigh !== []) {
                $items[] = [
                    'key' => 'weigh',
                    'title' => Tr::get('ওজন মাপুন: :tags', ['tags' => implode(', ', array_slice($toWeigh, 0, 6)).(count($toWeigh) > 6 ? '…' : '')]),
                    'detail' => Tr::get('মাসে অন্তত একবার — বাড়ছে কি না বোঝা যায়'),
                    'href' => '/batches',
                    'done' => false,
                ];
            }
        }

        foreach (Reminder::query()->visibleTo($user)->whereDate('due_at', '<=', $date)->orderBy('due_at')->limit(8)->get() as $reminder) {
            $items[] = [
                'key' => "reminder-{$reminder->id}",
                'title' => $reminder->title,
                'detail' => $reminder->due_at->lessThan($today) ? Tr::get('আগের দিনের — বাকি আছে') : $reminder->due_at->format('g:i A'),
                'href' => '/reminders',
                'done' => false,
            ];
        }

        return $items;
    }

    /**
     * Fattening animals not weighed in the last month.
     *
     * @return list<string> tags
     */
    private function toWeigh(CarbonInterface $today): array
    {
        $fattening = CostCenter::query()->where('code', 'MOTATAJA')->first();

        if ($fattening === null) {
            return [];
        }

        $since = $today->subDays(self::WEIGH_EVERY_DAYS)->toDateString();
        $recent = AnimalWeight::query()->whereDate('date', '>=', $since)->pluck('animal_id')->all();

        return array_values(Animal::query()->where('status', 'active')
            ->whereIn('cost_center_id', $fattening->descendantIds())
            ->whereNotIn('id', $recent)
            ->orderBy('tag')->get(['tag'])
            ->map(fn (Animal $animal) => $animal->tag)->all());
    }
}
