<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\KurbaniPlanner;
use App\Services\ProfitBook;
use App\Support\BanglaDate;
use App\Support\Money;
use App\Support\Tr;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** কোরবানি পরিকল্পনা: fattening rounds planned around Eid-ul-Adha. */
class KurbaniController extends Controller
{
    public function index(KurbaniPlanner $planner, ProfitBook $profit): Response
    {
        $defaults = $profit->plannerDefaults();

        return Inertia::render('herd/Kurbani', [
            'plan' => $planner->plan(),
            // Taka, for the client-side calculator.
            'calc' => [
                'animals' => 5,
                'buy_kg' => 200,
                'buy_price' => (int) $defaults['fattening_buy_price'],
                'gain' => KurbaniPlanner::ASSUMED_GAIN,
                'feed_per_day' => (int) $defaults['fattening_feed_per_day'],
                'sale_per_kg' => (int) round($planner->pricePerKg() / 100),
            ],
        ]);
    }

    public function updateEid(Request $request, KurbaniPlanner $planner): RedirectResponse
    {
        $data = $request->validate([
            'eid_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'price_per_kg' => ['nullable', 'numeric', 'min:50', 'max:5000'],
        ]);

        Setting::put(KurbaniPlanner::EID_KEY, $data['eid_date'] ?? null);
        Setting::put(KurbaniPlanner::PRICE_KEY, isset($data['price_per_kg']) ? (string) Money::toPoisha($data['price_per_kg']) : null);

        $eid = $planner->eid(CarbonImmutable::today())['date'];
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('ঈদের তারিখ :date ধরা হলো।', ['date' => BanglaDate::day($eid)])]);

        return back();
    }
}
