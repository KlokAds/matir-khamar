<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MarketPrice;
use App\Services\PriceBook;
use App\Support\Money;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** বাজারদর: haat and market prices, and what they suggest. */
class PriceController extends Controller
{
    public function index(PriceBook $book): Response
    {
        return Inertia::render('prices/Index', [
            'cards' => $book->latest(),
            'hints' => $book->suggestion(),
            // Commodity names stay Bangla: they are saved as data; the page shows them translated.
            'presets' => MarketPrice::PRESETS,
            'units' => Tr::all(MarketPrice::UNITS),
            'places' => MarketPrice::query()->whereNotNull('place')->orderByDesc('date')->limit(200)
                ->pluck('place')->unique()->take(12)->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'commodity' => ['required', 'string', 'max:120'],
            'unit' => ['required', Rule::in(array_keys(MarketPrice::UNITS))],
            'price' => Money::rule(),
            'place' => ['nullable', 'string', 'max:120'],
            'quality' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $commodity = trim($data['commodity']);

        MarketPrice::query()->create([
            ...$data,
            'commodity' => $commodity,
            'price' => Money::toPoisha($data['price']),
            'item_id' => Item::query()->where('name', $commodity)->value('id'),
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get(':name-এর দাম লেখা হলো।', ['name' => $commodity])]);

        return back();
    }

    public function destroy(MarketPrice $price): RedirectResponse
    {
        abort_unless($price->created_at?->greaterThan(now()->subDay()), 403, Tr::get('একদিনের বেশি পুরনো লেখা মোছা যায় না।'));
        $price->delete();

        return back();
    }
}
