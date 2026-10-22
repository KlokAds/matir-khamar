<?php

namespace App\Http\Controllers;

use App\Services\SummaryBook;
use App\Support\Tr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The whole farm on one page, for this month, last month, six months, the year, all time or any dates. */
class SummaryController extends Controller
{
    public function index(Request $request, SummaryBook $book): Response
    {
        $data = $request->validate([
            'period' => ['nullable', Rule::in(array_keys(SummaryBook::PERIODS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $period = $data['period'] ?? 'month';
        [$from, $to] = $book->range($period, $data['from'] ?? null, $data['to'] ?? null);

        return Inertia::render('summary/Index', [
            'period' => $period,
            'periods' => Tr::all(SummaryBook::PERIODS),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            ...$book->build($from, $to),
        ]);
    }
}
