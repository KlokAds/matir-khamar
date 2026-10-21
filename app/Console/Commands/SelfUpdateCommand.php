<?php

namespace App\Console\Commands;

use App\Services\SelfUpdater;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('khamar:update {--if-behind : Only when GitHub has a newer build (the scheduled check)}')]
#[Description('Update the live site from GitHub: backup, pull the built branch, migrate, rebuild caches')]
class SelfUpdateCommand extends Command
{
    public function handle(SelfUpdater $updater): int
    {
        if ($this->option('if-behind') && (! $updater->available() || $updater->behind() !== true)) {
            return self::SUCCESS;
        }

        $result = $updater->update();

        foreach ($result['log'] as $line) {
            $this->line($line);
        }

        if (! $result['ok']) {
            $this->components->error('Update failed.');

            return self::FAILURE;
        }

        $this->components->info('Up to date at '.substr((string) $result['to'], 0, 7).'.');

        return self::SUCCESS;
    }
}
