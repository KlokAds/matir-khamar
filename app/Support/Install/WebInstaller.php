<?php

namespace App\Support\Install;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Contracts\Console\Kernel;
use PDO;
use RuntimeException;
use Throwable;

/**
 * The browser installer (public/install.php): checks the server, writes .env, creates the
 * tables and the farm's starting chart of accounts, makes the first admin and locks itself.
 *
 * It runs before Laravel can boot (no .env, no APP_KEY yet), so everything up to install() is
 * plain PHP. Who may use it: whoever can read storage/app/install-token.txt on the server (made
 * on the first visit), so a stranger who finds a fresh site can't take it over. Once done it
 * writes storage/app/installed and never runs again; it also refuses wherever a working .env
 * already exists.
 */
class WebInstaller
{
    public const PHP_MIN = '8.3.0';

    public const EXTENSIONS = ['pdo_mysql', 'mbstring', 'intl', 'gd', 'openssl', 'fileinfo', 'dom', 'xml', 'tokenizer', 'ctype', 'curl'];

    /** Marks a .env this installer wrote but hasn't finished with (a failed run may be retried). */
    private const IN_PROGRESS = 'KHAMAR_INSTALLING=true';

    public function __construct(private string $base) {}

    public function lockFile(): string
    {
        return $this->base.'/storage/app/installed';
    }

    public function tokenFile(): string
    {
        return $this->base.'/storage/app/install-token.txt';
    }

    /** Closed when finished, or when a .env with a key exists that this installer didn't write. */
    public function closed(): bool
    {
        if (is_file($this->lockFile())) {
            return true;
        }

        $env = $this->base.'/.env';

        if (! is_file($env)) {
            return false;
        }

        $contents = (string) file_get_contents($env);

        return preg_match('/^APP_KEY=\S+/m', $contents) === 1 && ! str_contains($contents, self::IN_PROGRESS);
    }

    /** The setup code, made on first use; read it from the server to prove access. */
    public function token(): string
    {
        $file = $this->tokenFile();

        if (! is_file($file)) {
            @mkdir(dirname($file), 0755, true);
            file_put_contents($file, bin2hex(random_bytes(12)).PHP_EOL);
            @chmod($file, 0600);
        }

        return trim((string) file_get_contents($file));
    }

    public function tokenMatches(string $given): bool
    {
        return $given !== '' && hash_equals($this->token(), trim($given));
    }

    /** @return list<array{label: string, ok: bool, help: string}> */
    public function requirements(): array
    {
        $checks = [[
            'label' => 'PHP '.self::PHP_MIN.' বা নতুন (এখন '.PHP_VERSION.')',
            'ok' => version_compare(PHP_VERSION, self::PHP_MIN, '>='),
            'help' => 'hPanel → Advanced → PHP Configuration → PHP 8.4 বা 8.5 বাছুন।',
        ]];

        foreach (self::EXTENSIONS as $extension) {
            $checks[] = ['label' => "PHP extension {$extension}", 'ok' => extension_loaded($extension), 'help' => 'hPanel → PHP Configuration → PHP extensions থেকে চালু করুন।'];
        }

        foreach (['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $checks[] = ['label' => "{$dir}/ লেখা যায়", 'ok' => is_dir($this->base.'/'.$dir) && is_writable($this->base.'/'.$dir), 'help' => 'File Manager → ফোল্ডার → permission 755।'];
        }

        $checks[] = ['label' => 'অ্যাপের ফোল্ডারে .env লেখা যায়', 'ok' => is_writable($this->base), 'help' => 'File Manager → অ্যাপের ফোল্ডার → permission 755।'];
        $checks[] = ['label' => 'PHP প্যাকেজ আছে (vendor/)', 'ok' => is_file($this->base.'/vendor/autoload.php'), 'help' => 'hostinger-dist শাখা থেকে কোড নিন, সাধারণ সোর্স নয়।'];
        $checks[] = ['label' => 'তৈরি করা পাতাগুলো আছে (public/build/)', 'ok' => is_file($this->base.'/public/build/manifest.json'), 'help' => 'hostinger-dist শাখা থেকে কোড নিন।'];

        return $checks;
    }

    /** @param  array<string, string>  $in */
    public function databaseError(array $in): ?string
    {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $in['db_host'], (int) $in['db_port'], $in['db_name']),
                $in['db_user'],
                $in['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
            );
            $pdo->query('SELECT 1');

            $hasTable = $this->count($pdo, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'journal_entries'") > 0;
            $hasEntries = $hasTable && $this->count($pdo, 'SELECT COUNT(*) FROM journal_entries') > 0;

            return $hasEntries ? 'এই ডাটাবেসে আগে থেকেই খামারের হিসাব আছে। খালি ডাটাবেস দিন — পুরনো হিসাব মুছে যাওয়ার ঝুঁকি নেওয়া হবে না।' : null;
        } catch (Throwable $exception) {
            return 'ডাটাবেসে সংযোগ হয়নি: '.$exception->getMessage();
        }
    }

    private function count(PDO $pdo, string $sql): int
    {
        $statement = $pdo->query($sql);

        // Only reachable if the connection was opened without ERRMODE_EXCEPTION.
        if ($statement === false) {
            throw new RuntimeException('Query failed: '.$sql);
        }

        return (int) $statement->fetchColumn();
    }

    /**
     * The .env to write: the live-server template with the answers filled in.
     *
     * @param  array<string, string>  $in
     */
    public function envContents(array $in): string
    {
        $template = (string) file_get_contents($this->base.'/deploy/hostinger/env.example');
        $values = [
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_URL' => rtrim($in['app_url'], '/'),
            'DB_HOST' => $in['db_host'],
            'DB_PORT' => (string) (int) $in['db_port'],
            'DB_DATABASE' => $in['db_name'],
            'DB_USERNAME' => $in['db_user'],
            'DB_PASSWORD' => $in['db_pass'],
            'MAIL_FROM_ADDRESS' => $in['mail_from'],
            'MAIL_USERNAME' => $in['mail_from'],
            'MAIL_PASSWORD' => $in['mail_pass'],
            'MAIL_MAILER' => $in['mail_pass'] === '' ? 'log' : 'smtp',
        ];

        foreach ($values as $key => $value) {
            $line = $key.'='.self::quote($value);
            $template = preg_match("/^{$key}=.*$/m", $template)
                ? (string) preg_replace("/^{$key}=.*$/m", str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $template)
                : rtrim($template).PHP_EOL.$line.PHP_EOL;
        }

        return rtrim($template).PHP_EOL.self::IN_PROGRESS.PHP_EOL;
    }

    /** A value safe in .env: quoted when it has spaces, quotes, # or $, with those escaped. */
    public static function quote(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);

        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@+=-]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    /**
     * Step "build the database": write .env (marked in progress), create the tables and the
     * farm's starting chart of accounts. Safe to run again after a failure.
     *
     * @param  array<string, string>  $in
     * @return list<string>
     */
    public function prepareDatabase(array $in): array
    {
        @set_time_limit(0);
        ignore_user_abort(true);

        file_put_contents($this->base.'/.env', $this->envContents($in));
        @chmod($this->base.'/.env', 0600);
        @unlink($this->base.'/bootstrap/cache/config.php');

        $kernel = $this->boot();
        $log = [];

        $kernel->call('migrate', ['--force' => true]);
        $log[] = 'ডাটাবেসের টেবিল তৈরি হয়েছে।';

        $kernel->call('db:seed', ['--class' => KhamarSetupSeeder::class, '--force' => true]);
        $log[] = 'হিসাব খাত, ক্যাটাগরি, উদ্যোগ বাজেট ও স্টকের মাল সাজানো হয়েছে।';

        try {
            $kernel->call('storage:link');
        } catch (Throwable) {
            // Vouchers live on the private disk; the public link is only a convenience.
        }

        return $log;
    }

    /**
     * Step "admin": make the first hishab admin, then take the in-progress mark out, cache the
     * config and lock the installer for good.
     *
     * @return list<string>
     */
    public function finish(string $name, string $email, string $password): array
    {
        $kernel = $this->boot();

        if (User::query()->where('role', Role::Admin->value)->exists()) {
            throw new RuntimeException('একজন অ্যাডমিন আগে থেকেই আছে।');
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => Role::Admin,
            'is_active' => true,
        ])->forceFill(['email_verified_at' => now(), 'is_super' => true])->save();

        $env = (string) file_get_contents($this->base.'/.env');
        file_put_contents($this->base.'/.env', str_replace(self::IN_PROGRESS.PHP_EOL, '', $env));
        @unlink($this->base.'/bootstrap/cache/config.php');
        $kernel->call('optimize');
        file_put_contents($this->lockFile(), json_encode(['installed_at' => gmdate('c')]).PHP_EOL);
        @unlink($this->tokenFile());

        return [
            "অ্যাডমিন {$email} তৈরি হয়েছে।",
            'ইনস্টলার বন্ধ করা হয়েছে — আর কেউ এটা চালাতে পারবে না।',
        ];
    }

    private function boot(): Kernel
    {
        $app = require $this->base.'/bootstrap/app.php';
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();

        return $kernel;
    }
}
