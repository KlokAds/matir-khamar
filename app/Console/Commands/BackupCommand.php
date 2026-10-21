<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Nightly encrypted copy of the database. The file is useless without the
 * APP_KEY, so it can be copied off the server (Google Drive, a laptop)
 * safely. Keep a copy of the APP_KEY somewhere apart from the backups.
 */
#[Signature('khamar:backup {--keep=30 : How many daily backups to keep}')]
#[Description('Write an encrypted database backup to storage/app/private/backups')]
class BackupCommand extends Command
{
    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($config['driver'] === 'sqlite') {
            $dump = File::get($config['database']);
        } else {
            $process = new Process([
                'mysqldump', '--single-transaction', '--quick', '--no-tablespaces',
                '-h', $config['host'], '-P', (string) $config['port'], '-u', $config['username'], $config['database'],
            ], null, ['MYSQL_PWD' => $config['password']]);
            $process->setTimeout(600)->mustRun();
            $dump = $process->getOutput();
        }

        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory, 0700);
        $path = $directory.'/khamar-'.now()->format('Y-m-d-His').'.bak';

        $compressed = gzencode($dump, 9);

        // An empty file here would look like a backup and be trusted later.
        if ($compressed === false) {
            $this->error('backup failed: could not compress the dump');

            return self::FAILURE;
        }

        File::put($path, Crypt::encryptString(base64_encode($compressed)));
        chmod($path, 0600);

        $files = collect(File::files($directory))->sortByDesc(fn ($file) => $file->getMTime())->values();
        $files->slice((int) $this->option('keep'))->each(fn ($file) => File::delete($file->getPathname()));

        $this->info('backup written: '.basename($path).' ('.number_format(filesize($path) / 1024, 1).' KB)');

        return self::SUCCESS;
    }
}
