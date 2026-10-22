<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Item;
use Illuminate\Database\Seeder;

/**
 * What field crops need to start: the ধান চাষ project, its sale and own-straw accounts, and
 * the usual items (paddy, rice, own straw, seedlings, the three common fertilisers). Safe to
 * run again: it only adds what is missing, so it also runs from a migration on live installs.
 */
class CropSetupSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['4600', 'ফসল বিক্রি (ধান, চাল, আলু, সরিষা, ভুট্টা…)', 'income', null, '4000'],
            ['5130', 'খড় (নিজের জমির)', 'expense', 'internal', '5000'],
            ['5630', 'ফসল কাটা ও মাড়াই', 'expense', null, '5000'],
        ];

        foreach ($accounts as [$code, $name, $type, $subtype, $parent]) {
            if (Account::query()->where('code', $parent)->doesntExist()) {
                return;
            }

            Account::query()->firstOrCreate(['code' => $code], [
                'name' => $name,
                'type' => $type,
                'subtype' => $subtype,
                'parent_id' => Account::byCode($parent)->id,
                'is_system' => true,
            ]);
        }

        $account = fn (string $code) => Account::byCode($code)->id;

        foreach ([
            ['খড় (নিজের জমির)', 'kg', 'roughage', '1300', '5130'],
            ['ধান', 'kg', 'product', '1330', '5990'],
            ['চাল', 'kg', 'product', '1330', '5990'],
            ['ধানের বীজ / চারা', 'kg', 'crop_input', '1320', '5610'],
            ['ইউরিয়া সার', 'kg', 'crop_input', '1320', '5610'],
            ['টিএসপি সার', 'kg', 'crop_input', '1320', '5610'],
            ['পটাশ (এমওপি) সার', 'kg', 'crop_input', '1320', '5610'],
            ['আলু', 'kg', 'product', '1330', '5990'],
            ['আলুর বীজ', 'kg', 'crop_input', '1320', '5610'],
            ['সরিষা', 'kg', 'product', '1330', '5990'],
            ['সরিষার বীজ', 'kg', 'crop_input', '1320', '5610'],
            ['ভুট্টা', 'kg', 'product', '1330', '5990'],
            ['ভুট্টার বীজ', 'kg', 'crop_input', '1320', '5610'],
            ['ভুট্টার গাছ (গরুর খাবার)', 'kg', 'green', '1300', '5125'],
        ] as [$name, $unit, $kind, $inventory, $expense]) {
            Item::query()->firstOrCreate(['name' => $name], [
                'unit' => $unit,
                'kind' => $kind,
                'inventory_account_id' => $account($inventory),
                'expense_account_id' => $account($expense),
            ]);
        }

        $farm = CostCenter::query()->where('code', 'FARM')->first();

        if ($farm) {
            $fields = CostCenter::query()->firstOrCreate(['code' => 'FOSOL'], [
                'name' => 'মাঠের ফসল',
                'kind' => 'group',
                'parent_id' => $farm->id,
                'holds_animals' => false,
                'is_active' => true,
                'sort' => 24,
                'description' => 'ধান, আলু, সরিষা, ভুট্টা — প্রতিটি ফসল আলাদা প্রজেক্ট হিসেবে এর ভেতরে',
            ]);

            CostCenter::query()->firstOrCreate(['code' => 'DHAN'], [
                'name' => 'ধান চাষ',
                'kind' => 'crop',
                'parent_id' => $fields->id,
                'holds_animals' => false,
                'is_active' => true,
                'sort' => 25,
                'description' => 'লিজের জমিতে ধান — চাল বিক্রি, খড় গরুর জন্য',
            ]);
        }
    }
}
