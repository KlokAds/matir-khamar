<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\InvestorAccess;
use App\Models\Party;
use App\Models\User;
use App\Services\InvestorBook;
use App\Services\Ledger;
use App\Services\PartyAccounts;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));

    $this->goru = CostCenter::query()->where('code', 'GORU')->firstOrFail();
    $this->ghas = CostCenter::query()->where('code', 'GHAS')->firstOrFail();
    $ledger = app(Ledger::class);
    $cash = Account::byCode('1100')->id;
    $date = now()->toDateString();

    // Cow project (through its sub-categories): 50,000 milk sold, 20,000 spent on feed.
    $ledger->post('income', $date, 'দুধ বিক্রি — অক্টোবর', [
        ['account_id' => $cash, 'debit' => 5000000],
        ['account_id' => Account::byCode('4100')->id, 'credit' => 5000000, 'cost_center_id' => CostCenter::query()->where('code', 'GAVI')->value('id')],
    ]);
    $ledger->post('expense', $date, 'বকনার দানাদার খাদ্য', [
        ['account_id' => Account::byCode('5110')->id, 'debit' => 2000000, 'cost_center_id' => CostCenter::query()->where('code', 'BOKNA')->value('id')],
        ['account_id' => $cash, 'credit' => 2000000],
    ]);

    // Grass land, a different project: must never reach the cow investor.
    $ledger->post('expense', $date, 'ঘাসের জমিতে গোপন খরচ', [
        ['account_id' => Account::byCode('5110')->id, 'debit' => 900000, 'cost_center_id' => $this->ghas->id],
        ['account_id' => $cash, 'credit' => 900000],
    ]);

    // The investor put in 1,00,000 and took 10,000 back.
    $this->party = Party::query()->create(['type' => 'investor', 'name' => 'রহিম চাচা']);
    $capital = app(PartyAccounts::class)->capitalFor($this->party)->id;
    $ledger->post('investment', $date, 'বিনিয়োগ গ্রহণ — রহিম চাচা', [
        ['account_id' => $cash, 'debit' => 10000000],
        ['account_id' => $capital, 'credit' => 10000000],
    ], ['party_id' => $this->party->id]);
    $ledger->post('withdrawal', $date, 'মূলধন উত্তোলন — রহিম চাচা', [
        ['account_id' => $capital, 'debit' => 1000000],
        ['account_id' => $cash, 'credit' => 1000000],
    ], ['party_id' => $this->party->id]);

    $this->investor = User::factory()->create(['role' => Role::Viewer]);
    InvestorAccess::query()->create(['user_id' => $this->investor->id, 'party_id' => $this->party->id, 'cost_center_id' => $this->goru->id, 'share_percent' => 25]);
});

it('shows an investor only their project, its sub-categories included, with their share', function () {
    $book = app(InvestorBook::class)->forUser($this->investor);

    expect($book['projects'])->toHaveCount(1);
    $project = $book['projects'][0];

    expect($project['name'])->toBe('গরু প্রজেক্ট')
        ->and($project['year'])->toBe(['income' => 5000000, 'expense' => 2000000, 'profit' => 3000000])
        ->and($project['all_time']['profit'])->toBe(3000000)
        ->and($project['share_year'])->toBe(750000)
        ->and($project['share_all_time'])->toBe(750000)
        ->and(array_sum(array_column($project['trend'], 'income')))->toBe(5000000)
        ->and(array_sum(array_column($project['trend'], 'expense')))->toBe(2000000)
        ->and($project['trend'])->toHaveCount(12)
        ->and(array_column($project['recent'], 'description'))->toEqualCanonicalizing(['দুধ বিক্রি — অক্টোবর', 'বকনার দানাদার খাদ্য'])
        ->and(json_encode($book))->not->toContain('ঘাসের জমিতে গোপন খরচ');

    expect($book['capital'])->toMatchArray(['invested' => 10000000, 'withdrawn' => 1000000, 'net' => 9000000]);
});

it('keeps a second investor to their own project and shows no share when none is set', function () {
    $other = User::factory()->create(['role' => Role::Viewer]);
    InvestorAccess::query()->create(['user_id' => $other->id, 'cost_center_id' => $this->ghas->id]);

    $book = app(InvestorBook::class)->forUser($other);

    expect($book['projects'])->toHaveCount(1)
        ->and($book['projects'][0]['name'])->toBe($this->ghas->name)
        ->and($book['projects'][0]['year']['expense'])->toBe(900000)
        ->and($book['projects'][0]['share_year'])->toBeNull()
        ->and($book['capital']['invested'])->toBe(0)
        ->and(json_encode($book))->not->toContain('দুধ বিক্রি');
});

it('gives nothing to a login without access, and one project only once per login', function () {
    $stranger = User::factory()->create(['role' => Role::Viewer]);
    $book = app(InvestorBook::class);

    expect($book->hasAccess($stranger))->toBeFalse()
        ->and($book->forUser($stranger)['projects'])->toBe([])
        ->and($book->hasAccess($this->investor))->toBeTrue();

    expect(fn () => InvestorAccess::query()->create(['user_id' => $this->investor->id, 'cost_center_id' => $this->goru->id]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('counts all-time profit from the day the investor came in', function () {
    InvestorAccess::query()->where('user_id', $this->investor->id)->update(['since' => now()->addDay()->toDateString()]);

    $project = app(InvestorBook::class)->forUser($this->investor)['projects'][0];

    expect($project['all_time']['profit'])->toBe(0)
        ->and($project['year']['profit'])->toBe(3000000);
});
