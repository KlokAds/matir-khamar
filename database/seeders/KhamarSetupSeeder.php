<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BudgetHead;
use App\Models\CostCenter;
use App\Models\Item;
use Illuminate\Database\Seeder;

/**
 * Starting chart of accounts, categories and Udyog budget for Matir Khamar.
 * Safe to run again: every row is matched on its code first.
 */
class KhamarSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->accounts();
        $this->costCenters();
        $this->budgetHeads();
        $this->items();
        $this->call(CropSetupSeeder::class);
    }

    private function accounts(): void
    {
        $accounts = [
            // code, name, type, subtype, parent
            ['1000', 'সম্পদ', 'asset', null, null],
            ['1100', 'নগদ টাকা (ক্যাশ)', 'asset', 'cash', '1000'],
            ['1110', 'ব্যাংক হিসাব', 'asset', 'bank', '1000'],
            ['1120', 'বিকাশ', 'asset', 'wallet', '1000'],
            ['1130', 'নগদ (মোবাইল)', 'asset', 'wallet', '1000'],
            ['1200', 'ক্রেতার কাছে পাওনা (বাকি বিক্রি)', 'asset', 'receivable', '1000'],
            ['1300', 'খাদ্য স্টক', 'asset', 'inventory', '1000'],
            ['1310', 'ঔষধ ও টিকা স্টক', 'asset', 'inventory', '1000'],
            ['1320', 'বীজ, সার ও অন্যান্য স্টক', 'asset', 'inventory', '1000'],
            ['1330', 'উৎপাদিত পণ্যের স্টক', 'asset', 'inventory', '1000'],
            ['1400', 'গবাদিপশু (ক্রয়মূল্যে)', 'asset', 'livestock', '1000'],
            ['1500', 'জমি', 'asset', 'land', '1000'],
            ['1510', 'শেড ও ঘর', 'asset', 'fixed_asset', '1000'],
            ['1520', 'যন্ত্রপাতি ও সরঞ্জাম', 'asset', 'fixed_asset', '1000'],
            ['1600', 'অগ্রিম লিজ (জমি/পুকুর)', 'asset', 'prepaid', '1000'],
            ['1700', 'জমির জামানত / বন্ধক (ফেরতযোগ্য)', 'asset', null, '1000'],

            ['2000', 'দায়', 'liability', null, null],
            ['2100', 'সরবরাহকারীর পাওনা (বাকি ক্রয়)', 'liability', 'payable', '2000'],
            ['2200', 'ঋণ', 'liability', 'loan_group', '2000'],
            ['2300', 'উদ্যোগ অনুদান (শর্তসাপেক্ষ)', 'liability', 'grant', '2000'],

            ['3000', 'মূলধন', 'equity', null, null],
            ['3100', 'বিনিয়োগকারীর মূলধন', 'equity', 'capital_group', '3000'],
            ['3900', 'প্রারম্ভিক জের (আগের সম্পদ)', 'equity', 'opening', '3000'],

            ['4000', 'আয়', 'income', null, null],
            ['4100', 'দুধ বিক্রি', 'income', null, '4000'],
            ['4110', 'ঘি / ছানা / দই বিক্রি', 'income', null, '4000'],
            ['4200', 'পশু বিক্রি', 'income', null, '4000'],
            ['4300', 'গোবর / কেঁচো সার / বায়োগ্যাস', 'income', null, '4000'],
            ['4400', 'সবজি ও ফল বিক্রি', 'income', null, '4000'],
            ['4500', 'মাছ বিক্রি', 'income', null, '4000'],
            ['4900', 'অন্যান্য আয়', 'income', null, '4000'],
            ['4950', 'নিজস্ব উৎপাদন (অভ্যন্তরীণ)', 'income', 'internal', '4000'],

            ['5000', 'ব্যয়', 'expense', null, null],
            ['5100', 'খড় / ভুসি', 'expense', null, '5000'],
            ['5110', 'দানাদার খাদ্য', 'expense', null, '5000'],
            ['5120', 'কাঁচা ঘাস (কেনা)', 'expense', null, '5000'],
            ['5125', 'কাঁচা ঘাস (নিজের জমির)', 'expense', 'internal', '5000'],
            ['5200', 'ঔষধ ও টিকা', 'expense', null, '5000'],
            ['5210', 'পশু ডাক্তার', 'expense', null, '5000'],
            ['5300', 'শ্রমিক মজুরি', 'expense', null, '5000'],
            ['5400', 'বিদ্যুৎ', 'expense', null, '5000'],
            ['5410', 'পানি ও জ্বালানি', 'expense', null, '5000'],
            ['5500', 'পরিবহন', 'expense', null, '5000'],
            ['5600', 'জমি লিজ খরচ', 'expense', null, '5000'],
            ['5610', 'বীজ ও সার', 'expense', null, '5000'],
            ['5620', 'চাষ ও সেচ খরচ', 'expense', null, '5000'],
            ['5700', 'প্যাকেজিং ও ব্র্যান্ডিং', 'expense', null, '5000'],
            ['5710', 'বিপণন ও প্রচার', 'expense', null, '5000'],
            ['5720', 'নিবন্ধন ও লাইসেন্স', 'expense', null, '5000'],
            ['5730', 'প্রশিক্ষণ', 'expense', null, '5000'],
            ['5740', 'প্রযুক্তি ও ইন্টারনেট', 'expense', null, '5000'],
            ['5750', 'বীমা', 'expense', null, '5000'],
            ['5800', 'ঋণের সুদ', 'expense', null, '5000'],
            ['5850', 'বিক্রিত পশুর ক্রয়মূল্য', 'expense', 'animal_cost', '5000'],
            ['5860', 'পশু মৃত্যুজনিত ক্ষতি', 'expense', 'animal_cost', '5000'],
            ['5900', 'মেরামত ও রক্ষণাবেক্ষণ', 'expense', null, '5000'],
            ['5990', 'অন্যান্য খরচ', 'expense', null, '5000'],
        ];

        foreach ($accounts as [$code, $name, $type, $subtype, $parent]) {
            Account::query()->firstOrCreate(['code' => $code], [
                'name' => $name,
                'type' => $type,
                'subtype' => $subtype,
                'parent_id' => $parent ? Account::byCode($parent)->id : null,
                'is_system' => true,
            ]);
        }
    }

    private function costCenters(): void
    {
        $centers = [
            // code, name, kind, holds animals, parent, active, sort
            ['FARM', 'মাটির খামার (পুরো খামার)', 'group', false, null, true, 0],
            ['GORU', 'গরু প্রজেক্ট', 'group', false, 'FARM', true, 10],
            ['GAVI', 'গাভী (দুধ)', 'livestock', true, 'GORU', true, 11],
            ['BOKNA', 'বকনা', 'livestock', true, 'GORU', true, 12],
            ['MOTATAJA', 'মোটাতাজাকরণ', 'livestock', true, 'GORU', true, 13],
            ['BACHUR', 'বাছুর', 'livestock', true, 'GORU', true, 14],
            ['GHAS', 'ঘাস চাষ (জমি)', 'crop', false, 'FARM', true, 20],
            ['DUGDHO', 'দুগ্ধজাত পণ্য (ঘি, ছানা, দই)', 'processing', false, 'FARM', true, 30],
            ['JOIBO', 'বায়োগ্যাস ও কেঁচো সার', 'processing', false, 'FARM', true, 40],
            ['SHOBJI', 'সবজি ও ফল বাগান', 'crop', false, 'FARM', true, 50],
            ['MACH', 'মাছ চাষ (পুকুর)', 'fishery', false, 'FARM', false, 60],
            ['STORE', 'গ্রোসারি স্টোর', 'store', false, 'FARM', false, 70],
            ['SHADHARON', 'সাধারণ খরচ (পরে ভাগ হবে)', 'overhead', false, 'FARM', true, 90],
        ];

        foreach ($centers as [$code, $name, $kind, $holdsAnimals, $parent, $active, $sort]) {
            CostCenter::query()->firstOrCreate(['code' => $code], [
                'name' => $name,
                'kind' => $kind,
                'holds_animals' => $holdsAnimals,
                'parent_id' => $parent ? CostCenter::query()->where('code', $parent)->firstOrFail()->id : null,
                'is_active' => $active,
                'sort' => $sort,
            ]);
        }
    }

    /** Figures from the final Udyog application of 14 Oct 2026; edit them to match the approved letter. */
    private function budgetHeads(): void
    {
        $heads = [
            ['01', 'যন্ত্রপাতি ও সরঞ্জাম', 'শেড বড় করা, ঘি-ছানার সরঞ্জাম, বায়োগ্যাস, পাম্প, ঘাস কাটার মেশিন', 450000],
            ['02', 'কাঁচামাল ও উপকরণ', '২টি দুধেল গাভী, ৩টি বকনা, চারা-বীজ-সার, প্রাথমিক গোখাদ্য', 680000],
            ['03', 'পরিচলন ব্যয়', '৬ মাসের খাদ্য, ঔষধ, টিকা, বিদ্যুৎ', 320000],
            ['04', 'প্রোটোটাইপ', 'ঘি/ছানা/আচারের নমুনা উন্নয়ন', 20000],
            ['05', 'প্যাকেজিং ও ব্র্যান্ডিং', 'দুধের ক্যান, বোতল, QR লেবেল, সাইনবোর্ড', 50000],
            ['06', 'নিবন্ধন ও লাইসেন্স', 'ট্রেড লাইসেন্স, TIN, খামার নিবন্ধন', 30000],
            ['07', 'বিপণন ও প্রচার', 'ফেসবুক পেজ, লিফলেট, দোকানের সাথে চুক্তি', 70000],
            ['08', 'কর্মীর মজুরি', '২ জন × ৭,৫০০ × ১২ মাস', 180000],
            ['09', 'পরিবহন ও সরবরাহ', 'বাজারে ও হোম ডেলিভারিতে সরবরাহ', 60000],
            ['10', 'প্রযুক্তি', 'হিসাব সফটওয়্যার, QR সিস্টেম, ইন্টারনেট', 30000],
            ['11', 'প্রশিক্ষণ', 'প্রাণিসম্পদ অফিসের প্রশিক্ষণ', 30000],
            ['12', 'অন্যান্য', 'কেঁচো সার বেড, গরুর বীমা', 80000],
        ];

        foreach ($heads as $index => [$code, $name, $details, $taka]) {
            BudgetHead::query()->firstOrCreate(['code' => $code], [
                'name' => $name,
                'details' => $details,
                'approved_amount' => $taka * 100,
                'sort' => $index,
            ]);
        }
    }

    private function items(): void
    {
        $account = fn (string $code) => Account::byCode($code)->id;

        $items = [
            ['ভুসি', 'kg', 'roughage', '1300', '5100'],
            ['খড়', 'kg', 'roughage', '1300', '5100'],
            ['দানাদার খাদ্য (মিশ্রণ)', 'kg', 'concentrate', '1300', '5110'],
            ['খৈল', 'kg', 'concentrate', '1300', '5110'],
            ['কাঁচা ঘাস (কেনা)', 'kg', 'green', '1300', '5120'],
            ['কাঁচা ঘাস (নিজের জমির)', 'kg', 'green', '1300', '5125'],
            ['ঔষধ', 'pcs', 'medicine', '1310', '5200'],
            ['টিকা', 'dose', 'medicine', '1310', '5200'],
        ];

        foreach ($items as [$name, $unit, $kind, $inventory, $expense]) {
            Item::query()->firstOrCreate(['name' => $name], [
                'unit' => $unit,
                'kind' => $kind,
                'inventory_account_id' => $account($inventory),
                'expense_account_id' => $account($expense),
            ]);
        }
    }
}
