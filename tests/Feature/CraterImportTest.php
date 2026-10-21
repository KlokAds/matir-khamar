<?php

use App\Models\Account;
use App\Models\CostCenter;
use App\Models\ImportedRecord;
use App\Models\ImportMapping;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\CraterImporter;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create());

    // A small stand-in for the billing.klokads.com (Crater) database.
    config(['database.connections.crater' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false]]);
    DB::purge('crater');
    $schema = DB::connection('crater')->getSchemaBuilder();

    foreach (['expense_categories', 'payment_methods', 'customers'] as $table) {
        $schema->create($table, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('company_id')->default(1);
        });
    }

    $schema->create('expenses', function (Blueprint $table) {
        $table->id();
        $table->date('expense_date');
        $table->bigInteger('amount');
        $table->text('notes')->nullable();
        $table->integer('expense_category_id');
        $table->integer('payment_method_id')->nullable();
        $table->integer('company_id')->default(1);
    });

    $schema->create('payments', function (Blueprint $table) {
        $table->id();
        $table->string('payment_number');
        $table->date('payment_date');
        $table->bigInteger('amount');
        $table->text('notes')->nullable();
        $table->integer('customer_id');
        $table->integer('payment_method_id')->nullable();
        $table->integer('company_id')->default(1);
    });

    $crater = DB::connection('crater');
    $crater->table('expense_categories')->insert([['name' => 'Facebook Ads (agency)'], ['name' => 'Goru khabar'], ['name' => 'Doctor']]);
    $crater->table('payment_methods')->insert([['name' => 'Cash']]);
    $crater->table('customers')->insert([['name' => 'Agency client'], ['name' => 'Mishti dokan']]);
    $crater->table('expenses')->insert([
        ['expense_date' => '2026-10-03', 'amount' => 500000, 'notes' => 'ads', 'expense_category_id' => 1, 'payment_method_id' => 1],
        ['expense_date' => '2026-10-01', 'amount' => 600000, 'notes' => 'vushi 400kg', 'expense_category_id' => 2, 'payment_method_id' => 1],
        ['expense_date' => '2026-10-02', 'amount' => 150000, 'notes' => 'visit', 'expense_category_id' => 3, 'payment_method_id' => null],
    ]);
    $crater->table('payments')->insert([
        ['payment_number' => 'PAY-1', 'payment_date' => '2026-10-04', 'amount' => 900000, 'customer_id' => 1, 'payment_method_id' => 1],
        ['payment_number' => 'PAY-2', 'payment_date' => '2026-10-05', 'amount' => 120000, 'customer_id' => 2, 'payment_method_id' => 1],
    ]);

    $this->importer = app(CraterImporter::class);
    $this->map = fn (string $kind, string $name) => ImportMapping::query()->where('kind', $kind)->where('external_name', $name)->firstOrFail();
});

it('imports only farm rows, in billing order, with exact amounts', function () {
    $this->importer->fetch();
    $cash = Account::byCode('1100')->id;
    $vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();

    ($this->map)('payment_method', 'Cash')->update(['account_id' => $cash]);
    ($this->map)('payment_method', '(মাধ্যম দেওয়া নেই)')->update(['account_id' => $cash]);
    ($this->map)('expense_category', 'Goru khabar')->update(['item_id' => $vushi->id]);
    ($this->map)('expense_category', 'Doctor')->update(['account_id' => Account::byCode('5210')->id, 'cost_center_id' => CostCenter::query()->where('code', 'BOKNA')->value('id')]);
    ($this->map)('customer', 'Mishti dokan')->update(['account_id' => Account::byCode('4100')->id, 'cost_center_id' => CostCenter::query()->where('code', 'GAVI')->value('id')]);

    // The agency's own category and client are not farm.
    expect($this->importer->ignoreUndecided())->toBe(2);

    // The feed row needs its kg before anything after it may post.
    expect($this->importer->post()['posted'])->toBe(0);

    ImportedRecord::query()->where('external_table', 'expenses')->where('external_id', '2')->update(['qty' => 400]);
    $this->importer->post();

    $posted = ImportedRecord::query()->where('status', 'posted')->orderBy('journal_entry_id')->pluck('external_date')->map->toDateString()->all();

    expect($posted)->toBe(['2026-10-01', '2026-10-02', '2026-10-05'])
        ->and(ImportedRecord::query()->where('status', 'ignored')->count())->toBe(2)
        ->and($vushi->fresh()->qty_on_hand)->toBe(400.0)
        ->and($vushi->fresh()->value_on_hand)->toBe(600000)
        ->and(collect($this->importer->reconcile())->sum('difference'))->toBe(0);
});

it('never posts a billing row twice and flags rows changed after posting', function () {
    $this->importer->fetch();
    $cash = Account::byCode('1100')->id;
    ImportMapping::query()->where('kind', 'payment_method')->update(['account_id' => $cash]);
    ($this->map)('expense_category', 'Doctor')->update(['account_id' => Account::byCode('5210')->id, 'cost_center_id' => CostCenter::query()->where('code', 'BOKNA')->value('id')]);
    ($this->map)('expense_category', 'Goru khabar')->update(['ignore' => true]);
    $this->importer->ignoreUndecided();
    $this->importer->post();

    $before = JournalEntry::query()->count();
    $this->importer->fetch();
    $this->importer->post();
    expect(JournalEntry::query()->count())->toBe($before);

    DB::connection('crater')->table('expenses')->where('id', 3)->update(['amount' => 170000]);
    expect($this->importer->fetch()['changed'])->toBe(1)
        ->and(ImportedRecord::query()->where('external_id', '3')->where('external_table', 'expenses')->value('status'))->toBe('changed');
});
