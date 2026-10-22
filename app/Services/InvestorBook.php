<?php

namespace App\Services;

use App\Models\InvestorAccess;
use App\Models\JournalLine;
use App\Models\Party;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * What an investor may see: only the projects granted to their login, each with its own
 * income, costs and profit, plus the capital they put in. Every figure comes from the same
 * journal lines as the owner's reports, so both always tell the same story.
 */
class InvestorBook
{
    /** Latest entries looked at when picking the big ones of a project. */
    private const RECENT_POOL = 30;

    public function __construct(private ReportService $reports) {}

    /**
     * @return array{
     *     projects: list<array<string, mixed>>,
     *     capital: array{invested:int, withdrawn:int, net:int, parties: list<array{id:int, name:string, invested:int, withdrawn:int, net:int}>}
     * }
     */
    public function forUser(User $user): array
    {
        $grants = InvestorAccess::query()->with(['costCenter', 'party'])->where('user_id', $user->id)->get()
            ->sortBy(fn (InvestorAccess $access) => sprintf('%06d %s', $access->costCenter->sort, $access->costCenter->name));

        $projects = [];

        foreach ($grants as $access) {
            $projects[] = $this->project($access);
        }

        /** @var list<int> $partyIds */
        $partyIds = $grants->pluck('party_id')->filter()->unique()->map(fn ($id) => (int) $id)->values()->all();

        return ['projects' => $projects, 'capital' => $this->capital($partyIds)];
    }

    /**
     * One granted project, its sub-categories included.
     *
     * @return array<string, mixed>
     */
    public function project(InvestorAccess $access): array
    {
        $center = $access->costCenter;
        $ids = $center->descendantIds();
        $now = CarbonImmutable::now();

        $year = $this->result($now->startOfYear(), $now->endOfDay(), $ids);
        // "All time" for this investor starts the day they came in, when that is known.
        $allTime = $this->result($access->since ?? CarbonImmutable::create(2000, 1, 1), $now->endOfDay(), $ids);
        $share = $access->share_percent;

        return [
            'id' => $access->id,
            'center_id' => $center->id,
            'name' => $center->name,
            'description' => $center->description,
            'share_percent' => $share,
            'since' => $access->since?->toDateString(),
            'year_label' => $now->year,
            'year' => $year,
            'all_time' => $allTime,
            'share_year' => $share === null ? null : $this->shareOf($year['profit'], $share),
            'share_all_time' => $share === null ? null : $this->shareOf($allTime['profit'], $share),
            'trend' => $this->reports->monthlyTrend(12, $ids, false),
            'recent' => $this->recent($ids),
        ];
    }

    /**
     * Money each investor party put in and took out, from their own capital accounts.
     *
     * @param  list<int>  $partyIds
     * @return array{invested:int, withdrawn:int, net:int, parties: list<array{id:int, name:string, invested:int, withdrawn:int, net:int}>}
     */
    public function capital(array $partyIds): array
    {
        if ($partyIds === []) {
            return ['invested' => 0, 'withdrawn' => 0, 'net' => 0, 'parties' => []];
        }

        $sums = JournalLine::query()->toBase()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('accounts.type', 'equity')
            ->whereIn('accounts.party_id', $partyIds)
            ->groupBy('accounts.party_id')
            ->selectRaw('accounts.party_id as party, SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c')
            ->get()
            ->keyBy('party');

        $parties = [];
        $invested = 0;
        $withdrawn = 0;

        foreach (Party::query()->whereIn('id', $partyIds)->orderBy('name')->get() as $party) {
            $row = $sums->get($party->id);
            $in = (int) data_get($row, 'c', 0);
            $out = (int) data_get($row, 'd', 0);
            $invested += $in;
            $withdrawn += $out;
            $parties[] = ['id' => $party->id, 'name' => $party->name, 'invested' => $in, 'withdrawn' => $out, 'net' => $in - $out];
        }

        return ['invested' => $invested, 'withdrawn' => $withdrawn, 'net' => $invested - $withdrawn, 'parties' => $parties];
    }

    /** A share of a profit (or loss) in poisha. */
    public function shareOf(int $profit, float $percent): int
    {
        return (int) round($profit * $percent / 100);
    }

    /** Whether this login has at least one project to see. */
    public function hasAccess(User $user): bool
    {
        return InvestorAccess::query()->where('user_id', $user->id)->exists();
    }

    /**
     * @param  list<int>  $ids
     * @return array{income:int, expense:int, profit:int}
     */
    private function result(CarbonInterface $from, CarbonInterface $to, array $ids): array
    {
        $totals = $this->reports->accountTotals($from, $to, $ids);

        return ['income' => $totals['income'], 'expense' => $totals['expense'], 'profit' => $totals['income'] - $totals['expense']];
    }

    /**
     * The biggest of the project's latest entries, newest first. Reversed entries and their
     * reversals are left out, since together they never happened.
     *
     * @param  list<int>  $ids
     * @return list<array{date:string, description:string, kind:string, amount:int}>
     */
    private function recent(array $ids): array
    {
        $rows = JournalLine::query()->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('accounts.type', ['income', 'expense'])
            ->whereIn('journal_lines.cost_center_id', $ids)
            ->whereNull('journal_entries.reversed_by_id')
            ->whereNull('journal_entries.reversal_of_id')
            ->groupBy('journal_entries.id', 'journal_entries.date', 'journal_entries.description')
            ->selectRaw("journal_entries.id as id, journal_entries.date as date, journal_entries.description as description,
                SUM(CASE WHEN accounts.type = 'income' THEN journal_lines.credit - journal_lines.debit ELSE 0 END) as income,
                SUM(CASE WHEN accounts.type = 'expense' THEN journal_lines.debit - journal_lines.credit ELSE 0 END) as expense")
            ->orderByDesc('journal_entries.date')
            ->orderByDesc('journal_entries.id')
            ->limit(self::RECENT_POOL)
            ->get();

        $entries = [];

        foreach ($rows as $row) {
            $income = (int) data_get($row, 'income');
            $expense = (int) data_get($row, 'expense');

            if ($income === $expense) {
                continue;
            }

            $entries[] = [
                'order' => substr((string) data_get($row, 'date'), 0, 10).sprintf('%012d', (int) data_get($row, 'id')),
                'date' => substr((string) data_get($row, 'date'), 0, 10),
                'description' => (string) data_get($row, 'description'),
                'kind' => $income > $expense ? 'income' : 'expense',
                'amount' => abs($income - $expense),
            ];
        }

        usort($entries, fn (array $a, array $b) => $b['amount'] <=> $a['amount']);
        $entries = array_slice($entries, 0, 5);
        usort($entries, fn (array $a, array $b) => strcmp($b['order'], $a['order']));

        return array_map(fn (array $e) => ['date' => $e['date'], 'description' => $e['description'], 'kind' => $e['kind'], 'amount' => $e['amount']], $entries);
    }
}
