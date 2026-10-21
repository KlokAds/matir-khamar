<?php

namespace App\Services;

use App\Support\PhpCli;
use App\Support\Tr;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

/**
 * Brings the live site up to date with GitHub. Every push to main is built by the
 * "Hostinger deploy" workflow into the hostinger-dist branch (with vendor/ and public/build, since
 * the host has no Node); this checks that branch out, takes a database backup, migrates and
 * rebuilds the caches. .env, uploads and backups are untracked (deploy/hostinger/dist.gitignore),
 * so the forced checkout never touches them.
 *
 * Commands run as separate processes with the CLI php matching the web one, so the new code is
 * loaded fresh rather than mixed into this request.
 */
class SelfUpdater
{
    public const LAST_RUN_KEY = 'khamar:self-update:last';

    public function available(): bool
    {
        return is_dir(base_path('.git'));
    }

    public function branch(): string
    {
        return (string) config('khamar.updates.branch', 'hostinger-dist');
    }

    /** Null when GitHub can't be reached. */
    public function behind(): ?bool
    {
        $latest = $this->latest();

        return $latest === null ? null : trim($this->git(['rev-parse', 'HEAD'])->output()) !== $latest;
    }

    /**
     * @return array{available: bool, branch: string, current: ?array{sha: string, subject: string, date: string}, latest: ?string, behind: ?bool, last_run: ?array<string, mixed>}
     */
    public function status(): array
    {
        $status = [
            'available' => $this->available(),
            'branch' => $this->branch(),
            'current' => null,
            'latest' => null,
            'behind' => null,
            'last_run' => Cache::get(self::LAST_RUN_KEY),
        ];

        if (! $status['available']) {
            return $status;
        }

        $head = $this->git(['log', '-1', '--format=%H%x1f%s%x1f%cI']);

        if ($head->successful() && str_contains($head->output(), "\x1f")) {
            [$sha, $subject, $date] = explode("\x1f", trim($head->output()), 3);
            $status['current'] = compact('sha', 'subject', 'date');
        }

        $status['latest'] = $this->latest();
        $status['behind'] = $status['latest'] !== null && $status['current'] !== null ? $status['current']['sha'] !== $status['latest'] : null;

        return $status;
    }

    /**
     * @return array{ok: bool, from: ?string, to: ?string, log: list<string>, at: string}
     */
    public function update(): array
    {
        $lock = Cache::lock('khamar:self-update', 900);

        if (! $lock->get()) {
            throw new RuntimeException(Tr::get('একটি আপডেট ইতিমধ্যে চলছে।'));
        }

        $log = [];
        $from = $to = null;
        $ok = false;

        try {
            if (! $this->available()) {
                throw new RuntimeException(Tr::get('এই সার্ভারের কোড git checkout নয়, তাই নিজে আপডেট হতে পারে না।'));
            }

            $artisan = fn (string $command, int $timeout = 300) => $this->run($log, "artisan {$command}",
                $this->process($timeout)->run(array_merge([PhpCli::path(), 'artisan'], explode(' ', $command), ['--no-interaction'])));

            $from = trim($this->run($log, 'git rev-parse HEAD', $this->git(['rev-parse', 'HEAD']))->output());

            // The books come first: a backup before any new code touches the database.
            $artisan('khamar:backup', 600);

            $this->run($log, 'git fetch', $this->git(['fetch', '--prune', 'origin', $this->branch()], 180));
            $this->run($log, 'git checkout', $this->git(['checkout', '--force', '-B', $this->branch(), 'FETCH_HEAD']));
            $to = trim($this->git(['rev-parse', 'HEAD'])->output());

            if ($from === $to) {
                $log[] = Tr::get('আগে থেকেই সর্বশেষ সংস্করণ।');
            }

            $artisan('down --retry=30');

            try {
                $artisan('migrate --force', 600);
                $artisan('optimize:clear');
                $artisan('optimize');
            } finally {
                $artisan('up');
            }

            $ok = true;
        } catch (Throwable $exception) {
            $log[] = Tr::get('ব্যর্থ: :error', ['error' => $this->clean($exception->getMessage())]);
        } finally {
            $lock->release();
        }

        $result = ['ok' => $ok, 'from' => $from, 'to' => $to, 'log' => $log, 'at' => now()->toIso8601String()];
        Cache::forever(self::LAST_RUN_KEY, $result);

        return $result;
    }

    private function latest(): ?string
    {
        $remote = $this->git(['ls-remote', 'origin', 'refs/heads/'.$this->branch()], 30);

        return $remote->successful() && preg_match('/^([0-9a-f]{40})\s/', $remote->output(), $match) ? $match[1] : null;
    }

    /** @param  list<string>  $args */
    private function git(array $args, int $timeout = 60): ProcessResult
    {
        // The web server's environment has no HOME, so git would not find the stored GitHub login.
        return $this->process($timeout)->run(array_merge(['git', '-c', 'credential.helper=store'], $args));
    }

    private function process(int $timeout): PendingProcess
    {
        return Process::path(base_path())->timeout($timeout)->env([
            'HOME' => $this->home(),
            'GIT_TERMINAL_PROMPT' => '0',
        ]);
    }

    /** @param  list<string>  $log */
    private function run(array &$log, string $label, ProcessResult $result): ProcessResult
    {
        $output = $this->clean(trim($result->output()."\n".$result->errorOutput()));
        $log[] = ($result->successful() ? '✓ ' : '✗ ').$label.($output !== '' ? "\n".mb_substr($output, -1500) : '');

        if ($result->failed()) {
            throw new RuntimeException(Tr::get(':label ব্যর্থ (exit :code).', ['label' => $label, 'code' => (int) $result->exitCode()]));
        }

        return $result;
    }

    private function home(): string
    {
        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? null);

        if (! $home && function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $home = posix_getpwuid(posix_geteuid())['dir'] ?? null;
        }

        return $home ?: dirname(base_path());
    }

    /** Never show a GitHub token that sits in a remote URL. */
    private function clean(string $text): string
    {
        return preg_replace('#(https?://)[^/@\s]+@#', '$1', $text) ?? $text;
    }
}
