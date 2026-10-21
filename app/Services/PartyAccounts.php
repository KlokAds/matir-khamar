<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Party;

/**
 * Each investor gets an own capital account and each lender an own loan
 * account, so the books show exactly how much every person put in or is owed.
 */
class PartyAccounts
{
    public function capitalFor(Party $party): Account
    {
        return $this->childFor($party, AccountCodes::CAPITAL, 'equity', 'capital', 'মূলধন');
    }

    public function loanFor(Party $party): Account
    {
        return $this->childFor($party, AccountCodes::LOANS, 'liability', 'loan', 'ঋণ');
    }

    private function childFor(Party $party, string $parentCode, string $type, string $subtype, string $label): Account
    {
        $existing = Account::query()->where('party_id', $party->id)->where('subtype', $subtype)->first();

        if ($existing) {
            return $existing;
        }

        $parent = Account::byCode($parentCode);
        $siblings = Account::query()->where('parent_id', $parent->id)->count();

        return Account::query()->create([
            'parent_id' => $parent->id,
            'code' => $parentCode.'-'.str_pad((string) ($siblings + 1), 2, '0', STR_PAD_LEFT),
            'name' => "{$label} — {$party->name}",
            'type' => $type,
            'subtype' => $subtype,
            'party_id' => $party->id,
            'is_system' => true,
        ]);
    }
}
