<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Party;
use App\Models\User;
use App\Services\Ledger;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($this->admin);
});

it('adds an expense head from the dropdown under the right group with a free code', function () {
    $this->post('/manage/account', ['name' => 'মাছের খাবার', 'extra' => ['group' => '5000']])->assertRedirect();

    $head = Account::query()->where('name', 'মাছের খাবার')->firstOrFail();
    expect($head->type)->toBe('expense')
        ->and($head->parent_id)->toBe(Account::byCode('5000')->id)
        ->and(Account::query()->where('code', $head->code)->count())->toBe(1);

    $this->post('/manage/account', ['name' => 'মাছের খাবার', 'extra' => ['group' => '5000']])->assertSessionHasErrors('name');
});

it('renames, deletes an unused one and only hides one that is in the books', function () {
    $this->post('/manage/party', ['name' => 'রহিম', 'extra' => ['party_type' => 'customer']]);
    $party = Party::query()->where('name', 'রহিম')->firstOrFail();
    expect($party->type)->toBe('customer');

    $this->put("/manage/party/{$party->id}", ['name' => 'রহিম মিয়া'])->assertRedirect();
    expect($party->fresh()->name)->toBe('রহিম মিয়া');

    $this->delete("/manage/party/{$party->id}")->assertRedirect();
    expect(Party::query()->find($party->id))->toBeNull();

    $this->post('/manage/account', ['name' => 'বাড়তি খরচ', 'extra' => ['group' => '5000']]);
    $head = Account::query()->where('name', 'বাড়তি খরচ')->firstOrFail();
    app(Ledger::class)->post('expense', now()->toDateString(), 'খরচ', [
        ['account_id' => $head->id, 'debit' => 100_00, 'cost_center_id' => CostCenter::query()->where('code', 'GAVI')->value('id')],
        ['account_id' => Account::byCode('1100')->id, 'credit' => 100_00],
    ]);

    $this->delete("/manage/account/{$head->id}")->assertRedirect();
    expect($head->fresh())->not->toBeNull()->and($head->fresh()->is_active)->toBeFalse();
});

it('never removes the app\'s own heads and projects, nor a money place that still holds money', function () {
    $this->delete('/manage/account/'.Account::byCode('1100')->id)->assertSessionHasErrors('name');
    $this->delete('/manage/center/'.CostCenter::query()->where('code', 'GAVI')->value('id'))->assertSessionHasErrors('name');

    $this->post('/manage/money', ['name' => 'বিকাশ', 'extra' => ['kind' => 'wallet']]);
    $wallet = Account::query()->where('name', 'বিকাশ')->firstOrFail();
    app(Ledger::class)->post('transfer', now()->toDateString(), 'সরানো', [
        ['account_id' => $wallet->id, 'debit' => 500_00],
        ['account_id' => Account::byCode('1100')->id, 'credit' => 500_00],
    ]);

    $this->delete("/manage/money/{$wallet->id}")->assertSessionHasErrors('name');
});

it('lets only those allowed manage each kind', function () {
    $this->actingAs(User::factory()->create(['role' => Role::Manager]));

    $this->post('/manage/party', ['name' => 'করিম'])->assertRedirect();
    $this->post('/manage/account', ['name' => 'নতুন খাত', 'extra' => ['group' => '5000']])->assertForbidden();
    $this->post('/manage/whatever', ['name' => 'x'])->assertNotFound();
});
