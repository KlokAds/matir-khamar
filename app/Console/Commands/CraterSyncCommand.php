<?php

namespace App\Console\Commands;

use App\Services\CraterImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('khamar:crater-sync {--fetch-only : Copy new rows but do not post them}')]
#[Description('Copy farm rows from billing.klokads.com and post the ones whose mapping is complete')]
class CraterSyncCommand extends Command
{
    public function handle(CraterImporter $importer): int
    {
        if (! $importer->configured()) {
            $this->warn('CRATER_DB_* is not set; nothing to sync.');

            return self::SUCCESS;
        }

        $fetched = $importer->fetch();
        $this->info("fetched: new {$fetched['new']}, changed {$fetched['changed']}, unchanged {$fetched['unchanged']}");

        if (! $this->option('fetch-only')) {
            $posted = $importer->post();
            $this->info("posted {$posted['posted']}, waiting {$posted['waiting']}, ignored {$posted['ignored']}, failed {$posted['failed']}");
        }

        return self::SUCCESS;
    }
}
