<?php

namespace App\Http\Controllers;

use App\Services\CashForecast;
use App\Services\DuesBook;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Money ahead: the coming months' cash, and who owes whom. */
class CashController extends Controller
{
    public function index(Request $request, CashForecast $forecast, DuesBook $dues): Response
    {
        $tab = $request->string('tab')->toString();

        return Inertia::render('cash/Index', [
            'tab' => in_array($tab, ['forecast', 'dues'], true) ? $tab : 'forecast',
            'forecast' => $forecast->months(3),
            'dues' => $dues->book(),
            'remindAfter' => DuesBook::REMIND_AFTER_DAYS,
        ]);
    }
}
