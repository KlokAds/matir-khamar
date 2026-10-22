<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\CropSeason;
use App\Models\Item;
use App\Models\Plot;
use App\Models\User;
use App\Services\CropBook;
use App\Services\HerdCare;
use App\Services\Ledger;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->dhan = CostCenter::query()->where('code', 'DHAN')->firstOrFail();
});

it('sets up field crops: the project, paddy, straw and the inputs', function () {
    expect(CostCenter::query()->where('code', 'FOSOL')->value('kind'))->toBe('group')
        ->and($this->dhan->parent->code)->toBe('FOSOL')
        ->and(Item::query()->whereIn('name', ['ধান', 'খড় (নিজের জমির)', 'ইউরিয়া সার', 'আলু', 'সরিষা', 'ভুট্টা'])->count())->toBe(6);
});

it('works out a paddy season per bigha, with straw valued for the cattle', function () {
    // 66 decimals = 2 bigha at 33 decimals a bigha.
    $this->post('/land/plots', ['name' => 'উত্তরের জমি', 'cost_center_id' => $this->dhan->id, 'area_decimals' => 66, 'tenure' => 'lease', 'owner_name' => 'করিম', 'lease_from' => '2026-06-01', 'lease_to' => '2027-05-31', 'yearly_rent' => '30000'])->assertRedirect();
    $plot = Plot::query()->firstOrFail();

    $this->post('/land/seasons', [
        'cost_center_id' => $this->dhan->id, 'crop' => 'ধান', 'name' => 'আমন ২০২৬', 'started_on' => now()->subMonths(4)->toDateString(),
        'plots' => [['plot_id' => $plot->id, 'area_decimals' => 66]],
    ])->assertRedirect();
    $season = CropSeason::query()->firstOrFail();

    // 20,000 taka of costs on the paddy project during the season.
    app(Ledger::class)->post('expense', now()->subMonths(3)->toDateString(), 'চাষ, সার, মজুরি', [
        ['account_id' => Account::byCode('5620')->id, 'debit' => 2000000, 'cost_center_id' => $this->dhan->id],
        ['account_id' => Account::byCode('1100')->id, 'credit' => 2000000],
    ]);

    $paddy = Item::query()->where('name', 'ধান')->firstOrFail();
    $straw = Item::query()->where('name', 'খড় (নিজের জমির)')->firstOrFail();

    // 2,400 kg paddy (60 মণ) at 30/kg, 3,000 kg straw at 2/kg.
    $this->post("/land/seasons/{$season->id}/harvest", ['date' => now()->toDateString(), 'outputs' => [
        ['item_id' => $paddy->id, 'qty' => 2400, 'rate' => 30],
        ['item_id' => $straw->id, 'qty' => 3000, 'rate' => 2],
    ]])->assertRedirect();

    $card = app(CropBook::class)->seasonCard($season->fresh());

    expect($card['bigha'])->toEqual(2.0)
        ->and($card['cost'])->toBe(2000000)
        ->and($card['value'])->toBe(7800000)
        ->and($card['profit'])->toBe(5800000)
        ->and($card['profit_per_bigha'])->toBe(2900000)
        ->and($card['main']['name'])->toBe('ধান')
        ->and($card['main']['mon_per_bigha'])->toEqual(30.0)
        // (20,000 − 6,000 straw) / 2,400 kg = 5.83 taka a kg of paddy.
        ->and($card['main']['cost_per_kg'])->toEqual(5.83)
        ->and($paddy->fresh()->qty_on_hand)->toEqual(2400.0);

    expect(app(CropBook::class)->plotYear($plot->fresh(), (int) now()->year)['profit'])->toBe(
        now()->subMonths(4)->year === now()->year ? 5800000 : 0,
    );

    $this->get('/land')->assertOk();
    $this->get("/land/seasons/{$season->id}")->assertOk();
});

it('reminds when a lease is about to end', function () {
    Plot::query()->create(['name' => 'দক্ষিণের জমি', 'area_decimals' => 33, 'tenure' => 'lease', 'owner_name' => 'রহিম', 'lease_to' => now()->addDays(10)->toDateString()]);

    $lease = collect(app(HerdCare::class)->reminders())->firstWhere('kind', 'lease');

    expect($lease['title'])->toContain('দক্ষিণের জমি')->toContain('10');
});

it('uses the farm\'s own bigha size', function () {
    $this->put('/setup/land/bigha', ['decimals' => 50])->assertRedirect();

    expect(app(CropBook::class)->bigha(100))->toEqual(2.0);
});
