<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CostCenter;
use App\Models\CropSeason;
use App\Models\Item;
use App\Models\Plot;
use App\Models\Setting;
use App\Services\CropBook;
use App\Support\Lookups;
use App\Support\Money;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The land the farm works and the crop seasons on it. */
class LandController extends Controller
{
    public function index(CropBook $book): Response
    {
        $plots = Plot::query()->with('costCenter:id,name')->orderByDesc('is_active')->orderBy('name')->get();

        return Inertia::render('land/Index', [
            'plots' => $plots->map(fn (Plot $p) => [...$book->plotRow($p), 'year' => $book->plotYear($p, (int) now()->year)]),
            'crops' => CropSeason::CROPS,
            'seasons' => CropSeason::query()->orderByRaw('closed_on IS NOT NULL')->orderByDesc('started_on')->get()
                ->map(fn (CropSeason $s) => $book->seasonCard($s)),
            'bighaDecimals' => $book->bighaDecimals(),
            'bighaSizes' => Plot::BIGHA_SIZES,
            'tenures' => Tr::all(Plot::TENURES),
            'centers' => array_values(array_filter(Lookups::centers(), fn ($c) => ! $c['holds_animals'] && $c['kind'] !== 'overhead')),
            'defaultCenterId' => CostCenter::query()->where('code', 'DHAN')->value('id'),
        ]);
    }

    public function season(CropSeason $season, CropBook $book): Response
    {
        return Inertia::render('land/Season', [
            'season' => $book->seasonCard($season),
            'bighaDecimals' => $book->bighaDecimals(),
            'harvestItems' => Item::query()->where('is_active', true)->whereIn('kind', ['product', 'roughage', 'green'])
                ->orderBy('kind')->orderBy('name')->get(['id', 'name', 'unit', 'kind'])
                ->map(fn (Item $i) => ['id' => $i->id, 'name' => $i->name, 'unit' => Tr::get(Item::UNITS[$i->unit] ?? $i->unit), 'kind' => $i->kind]),
            'plots' => Plot::query()->where('is_active', true)->orderBy('name')->get()->map(fn (Plot $p) => $book->plotRow($p)),
            'crops' => CropSeason::CROPS,
            'centers' => array_values(array_filter(Lookups::centers(), fn ($c) => ! $c['holds_animals'] && $c['kind'] !== 'overhead')),
        ]);
    }

    /** A new crop's produce (e.g. তিল, মসুর) added from the season page; no code needed. */
    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:items,name'],
            'kind' => ['required', Rule::in(['product', 'roughage', 'green', 'crop_input'])],
        ]);

        [$inventory, $expense] = match ($data['kind']) {
            'product' => ['1330', '5990'],
            'crop_input' => ['1320', '5610'],
            default => ['1300', '5125'],
        };

        Item::query()->create([
            'name' => $data['name'],
            'unit' => 'kg',
            'kind' => $data['kind'],
            'inventory_account_id' => Account::byCode($inventory)->id,
            'expense_account_id' => Account::byCode($expense)->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('":name" মালের তালিকায় যোগ হলো।', ['name' => $data['name']])]);

        return back();
    }

    public function storePlot(Request $request): RedirectResponse
    {
        $plot = Plot::query()->create($this->plotData($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('":name" জমির তালিকায় যোগ হলো।', ['name' => $plot->name])]);

        return back();
    }

    public function updatePlot(Request $request, Plot $plot): RedirectResponse
    {
        $plot->update($this->plotData($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('জমির তথ্য হালনাগাদ হলো।')]);

        return back();
    }

    public function storeSeason(Request $request): RedirectResponse
    {
        $data = $this->seasonData($request);
        $season = DB::transaction(function () use ($data) {
            $season = CropSeason::query()->create(Arr::except($data, ['plots']));
            $season->plots()->sync($this->plotAreas($data['plots']));

            return $season;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('মৌসুম ":name" শুরু হলো।', ['name' => $season->name])]);

        return to_route('land.season', $season);
    }

    public function updateSeason(Request $request, CropSeason $season): RedirectResponse
    {
        $data = $this->seasonData($request, $season);
        DB::transaction(function () use ($season, $data) {
            $season->update(Arr::except($data, ['plots']));
            $season->plots()->sync($this->plotAreas($data['plots']));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('মৌসুম হালনাগাদ হলো।')]);

        return back();
    }

    public function harvest(Request $request, CropSeason $season, CropBook $book): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$season->started_on->toDateString()],
            'outputs' => ['required', 'array', 'min:1', 'max:10'],
            'outputs.*.item_id' => ['required', 'exists:items,id'],
            'outputs.*.qty' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'outputs.*.rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $entries = $book->harvest($season, $data['date'], $data['outputs']);
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('ফসল স্টকে উঠলো (:n টি এন্ট্রি)।', ['n' => count($entries)])]);

        return back();
    }

    public function bigha(Request $request): RedirectResponse
    {
        $data = $request->validate(['decimals' => ['required', 'numeric', 'min:10', 'max:200']]);
        Setting::put('land.bigha_decimals', (string) round((float) $data['decimals'], 2));
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('এক বিঘা = :n শতাংশ ধরা হলো।', ['n' => $data['decimals']])]);

        return back();
    }

    /**
     * @param  array<int, array{plot_id:int|string, area_decimals:float|string}>  $plots
     * @return array<int, array{area_decimals:float}>
     */
    private function plotAreas(array $plots): array
    {
        $areas = [];

        foreach ($plots as $plot) {
            $areas[(int) $plot['plot_id']] = ['area_decimals' => (float) $plot['area_decimals']];
        }

        return $areas;
    }

    /** @return array<string, mixed> */
    private function plotData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'area_decimals' => ['required', 'numeric', 'min:0.5', 'max:100000'],
            'bigha_decimals' => ['nullable', 'numeric', 'min:10', 'max:200'],
            'tenure' => ['required', Rule::in(array_keys(Plot::TENURES))],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'owner_phone' => ['nullable', 'string', 'max:30'],
            'lease_from' => ['nullable', 'date'],
            'lease_to' => ['nullable', 'date', 'after:lease_from'],
            'yearly_rent' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $data['yearly_rent'] = isset($data['yearly_rent']) && $data['yearly_rent'] !== '' ? Money::toPoisha($data['yearly_rent']) : null;

        return $data;
    }

    /** @return array<string, mixed> */
    private function seasonData(Request $request, ?CropSeason $season = null): array
    {
        return $request->validate([
            'cost_center_id' => ['required', 'exists:cost_centers,id'],
            'crop' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:120'],
            'variety' => ['nullable', 'string', 'max:120'],
            'started_on' => ['required', 'date', 'before_or_equal:today'],
            'sown_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'harvested_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'closed_on' => ['nullable', 'date', 'after_or_equal:started_on', 'before_or_equal:today'],
            'target_mon_per_bigha' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'plots' => ['required', 'array', 'min:1', 'max:30'],
            'plots.*.plot_id' => ['required', 'distinct', 'exists:plots,id'],
            'plots.*.area_decimals' => ['required', 'numeric', 'min:0.5', 'max:100000'],
        ]);
    }
}
