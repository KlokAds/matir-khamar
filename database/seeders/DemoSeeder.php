<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\Party;
use App\Models\User;
use App\Services\AllocationService;
use App\Services\AnimalService;
use App\Services\Ledger;
use App\Services\PartyAccounts;
use App\Services\PrepaidService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use RuntimeException;

/**
 * Local preview only: the farm as it stood in October 2026 (3 bokna and one
 * motataja bull, Klok Ads' investment, a month of feed and costs).
 * Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo data is never seeded in production.');
        }

        Auth::login(User::query()->where('email', 'admin@khamar.test')->firstOrFail());

        $ledger = app(Ledger::class);
        $stock = app(StockService::class);
        $animals = app(AnimalService::class);
        $center = fn (string $code) => CostCenter::query()->where('code', $code)->value('id');
        $account = fn (string $code) => Account::byCode($code)->id;
        $cash = $account('1100');

        $klok = Party::query()->create(['type' => 'investor', 'name' => 'Klok Ads']);
        $ledger->post('investment', '2025-12-01', 'Klok Ads-এর প্রাথমিক বিনিয়োগ', [
            ['account_id' => $cash, 'debit' => 50000000],
            ['account_id' => app(PartyAccounts::class)->capitalFor($klok)->id, 'credit' => 50000000],
        ], ['party_id' => $klok->id]);

        foreach ([['B-01', 'লালী'], ['B-02', 'ধলি'], ['B-03', 'কালি']] as [$tag, $name]) {
            $animals->register(['tag' => $tag, 'name' => $name, 'sex' => 'female', 'breed' => 'ফ্রিজিয়ান ক্রস', 'cost_center_id' => $center('BOKNA'), 'acquisition' => 'purchase', 'acquired_on' => '2025-12-10'], 7000000, $cash);
        }
        $animals->register(['tag' => 'M-01', 'name' => 'রাজা', 'sex' => 'male', 'breed' => 'দেশি এঁড়ে', 'cost_center_id' => $center('MOTATAJA'), 'acquisition' => 'purchase', 'acquired_on' => '2025-12-10', 'notes' => 'ঈদে বিক্রির লক্ষ্য'], 13000000, $cash);

        $vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();
        $danadar = Item::query()->where('name', 'দানাদার খাদ্য (মিশ্রণ)')->firstOrFail();

        foreach (['2026-08', '2026-09', '2026-10'] as $month) {
            $stock->purchase($vushi->fresh(), "{$month}-02", 400, 600000, ['credit_account_id' => $cash, 'description' => 'ভুসি ১০ মণ']);
            $stock->purchase($danadar->fresh(), "{$month}-02", 200, 900000, ['credit_account_id' => $cash]);
            $stock->issue($vushi->fresh(), "{$month}-15", [['cost_center_id' => $center('BOKNA'), 'qty' => 270], ['cost_center_id' => $center('MOTATAJA'), 'qty' => 110]], 'মাসের ভুসি');
            $stock->issue($danadar->fresh(), "{$month}-15", [['cost_center_id' => $center('BOKNA'), 'qty' => 120], ['cost_center_id' => $center('MOTATAJA'), 'qty' => 70]], 'মাসের দানাদার');

            $ledger->post('expense', "{$month}-05", 'বিদ্যুৎ বিল', [
                ['account_id' => $account('5400'), 'debit' => 100000, 'cost_center_id' => $center('SHADHARON')],
                ['account_id' => $cash, 'credit' => 100000],
            ]);
            $ledger->post('expense', "{$month}-08", 'ঔষধ ও কৃমিনাশক', [
                ['account_id' => $account('5200'), 'debit' => 150000, 'cost_center_id' => $center('BOKNA')],
                ['account_id' => $cash, 'credit' => 150000],
            ]);
        }

        app(PrepaidService::class)->payLease('2026-08-01', 'ঘাসের জমি লিজ (১ বছর)', 2400000, 12, '2026-08-01', $center('GHAS'), $cash, null, null);

        $grass = Item::query()->where('name', 'কাঁচা ঘাস (নিজের জমির)')->firstOrFail();
        $stock->produce($grass, '2026-10-10', 1500, 300, $center('GHAS'), 'নেপিয়ার ঘাস কাটা');
        $stock->issue($grass->fresh(), '2026-10-12', [['cost_center_id' => $center('BOKNA'), 'qty' => 1000], ['cost_center_id' => $center('MOTATAJA'), 'qty' => 400]], 'কাঁচা ঘাস');

        // Electricity sat in "Shadharon" until shared by animal-days.
        $allocation = app(AllocationService::class);
        foreach (['2026-08', '2026-09'] as $month) {
            $date = Date::parse("{$month}-01");
            $allocation->post(CostCenter::query()->where('code', 'SHADHARON')->firstOrFail(), $date, $allocation->animalDays($date), 'animal_days');
        }

        Auth::logout();
    }
}
