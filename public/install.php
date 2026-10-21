<?php

/*
 * Matir Khamar web installer for the live server (Hostinger). Locks itself when done;
 * see App\Support\Install\WebInstaller.
 */

use App\Support\Install\WebInstaller;

$base = dirname(__DIR__);

if (! is_file($base.'/vendor/autoload.php')) {
    http_response_code(500);
    exit('vendor/ নেই: hostinger-dist শাখা থেকে কোড নিন, সাধারণ সোর্স নয়।');
}

require $base.'/vendor/autoload.php';

$installer = new WebInstaller($base);

if ($installer->closed()) {
    http_response_code(404);
    exit('Not found.');
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_name('khamar_install');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => $https]);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$defaults = [
    'token' => '', 'app_url' => ($https ? 'https://' : 'http://').$host,
    'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'mail_from' => '', 'mail_pass' => '', 'admin_name' => '', 'admin_email' => '', 'admin_password' => '', 'admin_password_again' => '',
];
$secret = ['db_pass', 'mail_pass', 'admin_password', 'admin_password_again'];
$in = $defaults;
$errors = [];
$log = null;
$requirements = $installer->requirements();
$ready = ! in_array(false, array_column($requirements, 'ok'), true);
$installer->token(); // make the code file on the first visit

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaults as $key => $_) {
        $in[$key] = in_array($key, $secret, true) ? (string) ($_POST[$key] ?? '') : trim((string) ($_POST[$key] ?? ''));
    }

    if (! hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'ফর্মের মেয়াদ শেষ। পাতাটি আবার খুলে চেষ্টা করুন।';
    } elseif (! $installer->tokenMatches($in['token'])) {
        $errors[] = 'সেটআপ কোড মেলেনি। File Manager-এ storage/app/install-token.txt খুলে কোডটি কপি করুন।';
    } elseif (! $ready) {
        $errors[] = 'আগে সার্ভারের সমস্যাগুলো ঠিক করুন।';
    } else {
        if (! filter_var($in['app_url'], FILTER_VALIDATE_URL) || ! str_starts_with($in['app_url'], 'https://')) {
            $errors[] = 'সাইটের ঠিকানা https:// দিয়ে শুরু হতে হবে (যেমন https://khamar.klokads.com)। অফলাইন মোড ও নিরাপত্তার জন্য https লাগবেই।';
        }

        foreach (['db_host' => 'ডাটাবেস হোস্ট', 'db_name' => 'ডাটাবেসের নাম', 'db_user' => 'ডাটাবেস ইউজার', 'admin_name' => 'আপনার নাম'] as $key => $label) {
            if ($in[$key] === '') {
                $errors[] = "{$label} দিন।";
            }
        }

        if (! filter_var($in['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'ইমেইল ঠিক নেই।';
        }

        if (strlen($in['admin_password']) < 12 || ! preg_match('/[a-z]/', $in['admin_password']) || ! preg_match('/[A-Z]/', $in['admin_password']) || ! preg_match('/\d/', $in['admin_password'])) {
            $errors[] = 'অ্যাডমিন পাসওয়ার্ড কমপক্ষে ১২ অক্ষরের, ছোট-বড় হাতের অক্ষর ও সংখ্যা মিলিয়ে দিন।';
        } elseif ($in['admin_password'] !== $in['admin_password_again']) {
            $errors[] = 'দুই জায়গায় অ্যাডমিন পাসওয়ার্ড মেলেনি।';
        }

        if ($in['mail_from'] !== '' && ! filter_var($in['mail_from'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'মেইল পাঠানোর ঠিকানা ঠিক নেই।';
        }

        if ($errors === [] && ($dbError = $installer->databaseError($in)) !== null) {
            $errors[] = $dbError;
        }

        if ($errors === []) {
            try {
                $log = $installer->install($in);
                session_destroy();
            } catch (Throwable $exception) {
                $errors[] = 'ইনস্টল থেমে গেছে: '.$exception->getMessage();
            }
        }
    }
}

$field = static function (string $name, string $label, array $options = []) use ($e, $in): string {
    $type = $options['type'] ?? 'text';
    $value = $type === 'password' ? '' : $in[$name];
    $attrs = ($options['required'] ?? false ? ' required' : '').(isset($options['auto']) ? ' autocomplete="'.$options['auto'].'"' : '');
    $hint = isset($options['hint']) ? '<small>'.$options['hint'].'</small>' : '';

    return '<label class="field"><span>'.$label.'</span><input type="'.$type.'" name="'.$name.'" value="'.$e($value).'"'.$attrs.'>'.$hint.'</label>';
};
$logo = '<svg viewBox="0 0 48 48" aria-hidden="true"><rect width="48" height="48" rx="11" fill="#f8f3e8"/><circle cx="31" cy="17" r="8" fill="#e2b53b"/><path d="M24 31c0-6 3-10 8-11-1 5-3 8-8 11Zm0 0c0-5-2.5-8-6.5-9 .8 4.2 2.6 6.8 6.5 9Z" fill="#5d8a2f"/><path d="M24 31v5" stroke="#5d8a2f" stroke-width="2" stroke-linecap="round"/><path d="M4 36c8-3 32-3 40 0M6 41c8-2.5 28-2.5 36 0" stroke="#c0683a" stroke-width="2.6" stroke-linecap="round" fill="none"/></svg>';
?><!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>মাটির খামার — ইনস্টল</title>
<link rel="icon" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="stylesheet" href="https://fonts.bunny.net/css?family=noto-sans-bengali:400,600|tiro-bangla:400">
<style>
    :root { --bg: #f7f2e7; --card: #fffdf8; --ink: #3a2d20; --muted: #7a6a55; --line: #e4d9c4; --paddy: #4f7f2a; --clay: #c0683a; --turmeric: #e2b53b; --bad: #b3361f; }
    @media (prefers-color-scheme: dark) { :root { --bg: #1b1d16; --card: #23261d; --ink: #ece6d6; --muted: #b3aa94; --line: #383b2f; --paddy: #8fc25b; --clay: #e08a5a; --bad: #ef7a62; } }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--bg); color: var(--ink); font: 15.5px/1.6 'Noto Sans Bengali', system-ui, sans-serif; }
    main { max-width: 760px; margin: 0 auto; padding: 32px 16px 64px; }
    header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; }
    header svg { width: 56px; height: 56px; flex: none; }
    h1 { font: 400 1.8rem/1.2 'Tiro Bangla', serif; margin: 0; }
    h2 { font: 400 1.2rem/1.3 'Tiro Bangla', serif; margin: 0 0 12px; }
    header p, .muted { color: var(--muted); margin: 2px 0 0; }
    section { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 18px; margin-bottom: 16px; }
    .grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .field { display: grid; gap: 4px; font-size: .93rem; font-weight: 600; }
    .field small { font-weight: 400; color: var(--muted); }
    input { font: inherit; font-weight: 400; padding: 9px 12px; border: 1px solid var(--line); border-radius: 9px; background: var(--bg); color: var(--ink); width: 100%; }
    input:focus { outline: 3px solid color-mix(in oklab, var(--paddy) 35%, transparent); border-color: var(--paddy); }
    ul.checks { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; }
    ul.checks li { display: flex; gap: 8px; align-items: baseline; }
    .ok { color: var(--paddy); } .bad { color: var(--bad); }
    .errors { border-color: var(--bad); background: color-mix(in oklab, var(--bad) 8%, var(--card)); }
    .errors li { color: var(--bad); }
    button { font: inherit; font-weight: 600; background: var(--paddy); color: #fff; border: 0; border-radius: 10px; padding: 12px 22px; cursor: pointer; width: 100%; box-shadow: inset 0 -2px 0 rgb(0 0 0 / .15); }
    @media (prefers-color-scheme: dark) { button { color: #17200f; } }
    a.button { display: inline-block; background: var(--paddy); color: #fff; text-decoration: none; padding: 12px 22px; border-radius: 10px; font-weight: 600; }
    code { background: var(--bg); border: 1px solid var(--line); border-radius: 6px; padding: 1px 6px; }
</style>
</head>
<body>
<main>
    <header>
        <?= $logo ?>
        <div>
            <h1>মাটির খামার — ইনস্টল</h1>
            <p>একবারই লাগবে। শেষ হলে এই পাতা নিজে থেকে বন্ধ হয়ে যাবে।</p>
        </div>
    </header>

<?php if ($log !== null) { ?>
    <section>
        <h2>ইনস্টল সম্পূর্ণ হয়েছে</h2>
        <ul class="checks"><?php foreach ($log as $line) { ?><li><span class="ok">✓</span><?= $e($line) ?></li><?php } ?></ul>
        <p class="muted" style="margin:14px 0">এখন লগইন করুন। প্রথমবার ২-ধাপ যাচাই (Google Authenticator) চালু করতে বলবে — এটা ছাড়া হিসাব খুলবে না।</p>
        <a class="button" href="/login">লগইন পাতায় যান</a>
    </section>
<?php } else { ?>
    <?php if ($errors !== []) { ?>
    <section class="errors"><ul class="checks"><?php foreach ($errors as $error) { ?><li>✗ <?= $e($error) ?></li><?php } ?></ul></section>
    <?php } ?>

    <section>
        <h2>১. সার্ভার পরীক্ষা</h2>
        <ul class="checks">
        <?php foreach ($requirements as $check) { ?>
            <li><span class="<?= $check['ok'] ? 'ok' : 'bad' ?>"><?= $check['ok'] ? '✓' : '✗' ?></span><span><?= $e($check['label']) ?><?php if (! $check['ok']) { ?> — <span class="muted"><?= $e($check['help']) ?></span><?php } ?></span></li>
        <?php } ?>
        </ul>
    </section>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">

        <section>
            <h2>২. সেটআপ কোড</h2>
            <p class="muted" style="margin:0 0 10px">hPanel → File Manager → <code>klokads_khamar_app/storage/app/install-token.txt</code> খুলে কোডটি কপি করুন। এতে বোঝা যায় আপনিই সার্ভারের মালিক।</p>
            <?= $field('token', 'সেটআপ কোড', ['required' => true]) ?>
        </section>

        <section>
            <h2>৩. সাইট ও ডাটাবেস</h2>
            <div class="grid">
                <?= $field('app_url', 'সাইটের ঠিকানা', ['required' => true, 'hint' => 'https:// সহ']) ?>
                <?= $field('db_host', 'ডাটাবেস হোস্ট', ['required' => true, 'hint' => 'Hostinger-এ সাধারণত localhost']) ?>
                <?= $field('db_port', 'পোর্ট', ['required' => true]) ?>
                <?= $field('db_name', 'ডাটাবেসের নাম', ['required' => true, 'hint' => 'যেমন u477584574_khamar']) ?>
                <?= $field('db_user', 'ডাটাবেস ইউজার', ['required' => true]) ?>
                <?= $field('db_pass', 'ডাটাবেস পাসওয়ার্ড', ['type' => 'password', 'auto' => 'new-password', 'hint' => 'hPanel-এ ডাটাবেস খোলার সময় যেটা দিয়েছিলেন']) ?>
            </div>
        </section>

        <section>
            <h2>৪. হিসাব অ্যাডমিন (আপনি)</h2>
            <div class="grid">
                <?= $field('admin_name', 'আপনার নাম', ['required' => true]) ?>
                <?= $field('admin_email', 'ইমেইল (লগইনের জন্য)', ['type' => 'email', 'required' => true, 'auto' => 'username']) ?>
                <?= $field('admin_password', 'পাসওয়ার্ড', ['type' => 'password', 'required' => true, 'auto' => 'new-password', 'hint' => 'কমপক্ষে ১২ অক্ষর, ছোট-বড় হাতের অক্ষর ও সংখ্যা']) ?>
                <?= $field('admin_password_again', 'পাসওয়ার্ড আবার', ['type' => 'password', 'required' => true, 'auto' => 'new-password']) ?>
            </div>
        </section>

        <section>
            <h2>৫. ইমেইল পাঠানো (ঐচ্ছিক)</h2>
            <p class="muted" style="margin:0 0 10px">পাসওয়ার্ড ভুলে গেলে রিসেট লিংক পাঠাতে লাগে। পরে .env থেকেও দেওয়া যায়।</p>
            <div class="grid">
                <?= $field('mail_from', 'Hostinger মেইল ঠিকানা', ['type' => 'email', 'hint' => 'যেমন khamar@klokads.com']) ?>
                <?= $field('mail_pass', 'মেইলের পাসওয়ার্ড', ['type' => 'password', 'auto' => 'new-password']) ?>
            </div>
        </section>

        <button type="submit" <?= $ready ? '' : 'disabled' ?>>ইনস্টল করুন</button>
    </form>
<?php } ?>
</main>
</body>
</html>
