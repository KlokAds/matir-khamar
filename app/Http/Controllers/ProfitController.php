<?php

namespace App\Http\Controllers;

use App\Services\ProfitBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

/** Where the farm makes money, and a planner for what to do next. */
class ProfitController extends Controller
{
    public function index(Request $request, ProfitBook $book): Response
    {
        $request->validate(['year' => ['nullable', 'integer', 'min:2020', 'max:2100']]);
        $year = $request->integer('year') ?: (int) now()->year;
        $from = Date::create($year, 1, 1)->startOfDay();
        $to = $year === (int) now()->year ? now()->endOfDay() : Date::create($year, 12, 31)->endOfDay();
        $ratios = $book->ratios($from, $to);

        return Inertia::render('profit/Index', [
            'year' => $year,
            'projects' => $book->projects($from, $to),
            'animals' => $book->animals($from, $to, $ratios['milk_price'] !== null ? (float) $ratios['milk_price'] : null),
            'ratios' => $ratios,
            'defaults' => $book->plannerDefaults(),
        ]);
    }
}
