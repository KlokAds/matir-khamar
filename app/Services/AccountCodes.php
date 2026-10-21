<?php

namespace App\Services;

/**
 * Fixed account codes the services post to. The seeder creates these, and
 * the owner can add more accounts around them without breaking postings.
 */
final class AccountCodes
{
    public const CASH = '1100';

    public const RECEIVABLE = '1200';

    public const LIVESTOCK = '1400';

    public const LAND = '1500';

    public const PREPAID_LEASE = '1600';

    public const PAYABLE = '2100';

    public const LOANS = '2200';

    public const GRANT = '2300';

    public const CAPITAL = '3100';

    public const OPENING = '3900';

    public const ANIMAL_SALES = '4200';

    public const INTERNAL_INCOME = '4950';

    public const LEASE_EXPENSE = '5600';

    public const LOAN_INTEREST = '5800';

    public const COST_OF_ANIMALS_SOLD = '5850';

    public const ANIMAL_LOSS = '5860';
}
