<?php

/*
 * Matir Khamar web installer for the live server (Hostinger), in five steps:
 * server check → database → build the database → admin → done.
 * Locks itself when done; see App\Support\Install\WebInstaller.
 */

use App\Support\Install\WebInstaller;

$base = dirname(__DIR__);

if (! is_file($base.'/vendor/autoload.php')) {
    http_response_code(500);
    exit('vendor/ নেই: hostinger-dist শাখা থেকে কোড নিন, সাধারণ সোর্স নয়।');
}

require $base.'/vendor/autoload.php';

$installer = new WebInstaller($base);
$https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_name('khamar_install');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => $https]);
session_start();

// The finish screen is shown once, right after the installer locked itself.
$finished = $_SESSION['finished'] ?? null;

if ($installer->closed() && $finished === null) {
    http_response_code(404);
    exit('Not found.');
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$_SESSION['step'] ??= 1;
$_SESSION['in'] ??= [];

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$in = $_SESSION['in'] + [
    'app_url' => ($https ? 'https://' : 'http://').$host,
    'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'mail_from' => '', 'mail_pass' => '', 'admin_name' => '', 'admin_email' => '',
];
$errors = [];
$requirements = $installer->requirements();
$ready = ! in_array(false, array_column($requirements, 'ok'), true);

if ($finished === null) {
    $installer->token(); // make the code file on the first visit
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $finished === null) {
    $action = (string) ($_POST['action'] ?? '');
    $post = static fn (string $key): string => trim((string) ($_POST[$key] ?? ''));

    if (! hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'ফর্মের মেয়াদ শেষ। পাতাটি আবার খুলে চেষ্টা করুন।';
    } elseif ($action === 'back') {
        // Once the tables exist, going back would only confuse: the database step is done.
        $_SESSION['step'] = max(1, min((int) $_SESSION['step'] - 1, empty($_SESSION['built']) ? 3 : 4));
    } elseif ($action === 'server') {
        if (! $ready) {
            $errors[] = 'আগে লাল চিহ্নিত সমস্যাগুলো ঠিক করুন, তারপর পাতাটি আবার খুলুন।';
        } elseif (! $installer->tokenMatches($post('token'))) {
            $errors[] = 'সেটআপ কোড মেলেনি। File Manager-এ storage/app/install-token.txt খুলে কোডটি কপি করুন।';
        } else {
            $_SESSION['token_ok'] = true;
            $_SESSION['step'] = 2;
        }
    } elseif (empty($_SESSION['token_ok'])) {
        $_SESSION['step'] = 1;
        $errors[] = 'আগে সেটআপ কোড দিন।';
    } elseif ($action === 'database') {
        foreach (['app_url', 'db_host', 'db_port', 'db_name', 'db_user', 'mail_from'] as $key) {
            $in[$key] = $post($key);
        }

        // A password box left empty on a second visit keeps the one given before.
        foreach (['db_pass', 'mail_pass'] as $key) {
            if ((string) ($_POST[$key] ?? '') !== '') {
                $in[$key] = (string) $_POST[$key];
            }
        }

        if (! filter_var($in['app_url'], FILTER_VALIDATE_URL) || ! str_starts_with($in['app_url'], 'https://')) {
            $errors[] = 'সাইটের ঠিকানা https:// দিয়ে শুরু হতে হবে। অফলাইন মোড ও নিরাপত্তার জন্য https লাগবেই।';
        }

        foreach (['db_host' => 'ডাটাবেস হোস্ট', 'db_name' => 'ডাটাবেসের নাম', 'db_user' => 'ডাটাবেস ইউজার'] as $key => $label) {
            if ($in[$key] === '') {
                $errors[] = "{$label} দিন।";
            }
        }

        if ($in['mail_from'] !== '' && ! filter_var($in['mail_from'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'মেইল পাঠানোর ঠিকানা ঠিক নেই।';
        }

        if ($errors === [] && ($dbError = $installer->databaseError($in)) !== null) {
            $errors[] = $dbError;
        }

        $_SESSION['in'] = $in;

        if ($errors === []) {
            $_SESSION['step'] = 3;
        }
    } elseif ($action === 'build' && (int) $_SESSION['step'] === 3) {
        try {
            $_SESSION['build_log'] = $installer->prepareDatabase($in);
            $_SESSION['built'] = true;
            $_SESSION['step'] = 4;
        } catch (Throwable $exception) {
            $errors[] = 'ডাটাবেস তৈরি থেমে গেছে: '.$exception->getMessage();
        }
    } elseif ($action === 'admin' && ! empty($_SESSION['built'])) {
        $in['admin_name'] = $post('admin_name');
        $in['admin_email'] = $post('admin_email');
        $password = (string) ($_POST['admin_password'] ?? '');
        $_SESSION['in'] = $in;

        if ($in['admin_name'] === '') {
            $errors[] = 'আপনার নাম দিন।';
        }

        if (! filter_var($in['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'ইমেইল ঠিক নেই।';
        }

        if (strlen($password) < 12 || ! preg_match('/[a-z]/', $password) || ! preg_match('/[A-Z]/', $password) || ! preg_match('/\d/', $password)) {
            $errors[] = 'পাসওয়ার্ড কমপক্ষে ১২ অক্ষরের, ছোট-বড় হাতের অক্ষর ও সংখ্যা মিলিয়ে দিন।';
        } elseif ($password !== (string) ($_POST['admin_password_again'] ?? '')) {
            $errors[] = 'দুই জায়গায় পাসওয়ার্ড মেলেনি।';
        }

        if ($errors === []) {
            try {
                $log = $installer->finish($in['admin_name'], $in['admin_email'], $password);
                // Forget every secret typed here; keep only what the last screen shows.
                $_SESSION = ['finished' => ['email' => $in['admin_email'], 'log' => array_merge($_SESSION['build_log'] ?? [], $log)], 'csrf' => $_SESSION['csrf']];
                $finished = $_SESSION['finished'];
            } catch (Throwable $exception) {
                $errors[] = 'অ্যাডমিন তৈরি হয়নি: '.$exception->getMessage();
            }
        }
    }
}

$step = $finished !== null ? 5 : (int) $_SESSION['step'];
$steps = [1 => 'সার্ভার', 2 => 'ডাটাবেস', 3 => 'তৈরি', 4 => 'অ্যাডমিন', 5 => 'শেষ'];

$field = static function (string $name, string $label, array $options = []) use ($e, $in): string {
    $type = $options['type'] ?? 'text';
    $value = $type === 'password' ? '' : ($in[$name] ?? '');
    $attrs = ($options['required'] ?? false ? ' required' : '').(isset($options['auto']) ? ' autocomplete="'.$options['auto'].'"' : '').(isset($options['placeholder']) ? ' placeholder="'.$e($options['placeholder']).'"' : '');
    $hint = isset($options['hint']) ? '<small>'.$options['hint'].'</small>' : '';

    return '<label class="field"><span>'.$label.'</span><input type="'.$type.'" name="'.$name.'" value="'.$e($value).'"'.$attrs.'>'.$hint.'</label>';
};
$hidden = '<input type="hidden" name="csrf" value="'.$e($_SESSION['csrf']).'">';
$logo = '<svg viewBox="0 0 48 48" aria-hidden="true"><rect width="48" height="48" rx="11" fill="#f8f3e8"/><circle cx="31" cy="17" r="8" fill="#e2b53b"/><path d="M24 31c0-6 3-10 8-11-1 5-3 8-8 11Zm0 0c0-5-2.5-8-6.5-9 .8 4.2 2.6 6.8 6.5 9Z" fill="#5d8a2f"/><path d="M24 31v5" stroke="#5d8a2f" stroke-width="2" stroke-linecap="round"/><path d="M4 36c8-3 32-3 40 0M6 41c8-2.5 28-2.5 36 0" stroke="#c0683a" stroke-width="2.6" stroke-linecap="round" fill="none"/></svg>';
?><!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>মাটির খামার — ইনস্টল (<?= $step ?>/5)</title>
<link rel="icon" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="stylesheet" href="https://fonts.bunny.net/css?family=noto-sans-bengali:400,600|tiro-bangla:400">
<style>
    :root { --bg: #f7f2e7; --card: #fffdf8; --ink: #3a2d20; --muted: #7a6a55; --line: #e4d9c4; --paddy: #4f7f2a; --clay: #c0683a; --turmeric: #e2b53b; --bad: #b3361f; }
    @media (prefers-color-scheme: dark) { :root { --bg: #1b1d16; --card: #23261d; --ink: #ece6d6; --muted: #b3aa94; --line: #383b2f; --paddy: #8fc25b; --clay: #e08a5a; --bad: #ef7a62; } }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--bg); color: var(--ink); font: 15.5px/1.6 'Noto Sans Bengali', system-ui, sans-serif; }
    main { max-width: 640px; margin: 0 auto; padding: 32px 16px 64px; }
    header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; }
    header svg { width: 52px; height: 52px; flex: none; }
    h1 { font: 400 1.7rem/1.2 'Tiro Bangla', serif; margin: 0; }
    h2 { font: 400 1.35rem/1.3 'Tiro Bangla', serif; margin: 0 0 6px; }
    .muted { color: var(--muted); margin: 0 0 16px; }
    .progress { margin-bottom: 18px; }
    .bar { height: 8px; border-radius: 99px; background: var(--line); overflow: hidden; }
    .bar i { display: block; height: 100%; background: linear-gradient(90deg, var(--paddy), var(--turmeric)); border-radius: 99px; transition: width .4s; }
    .labels { display: flex; justify-content: space-between; margin-top: 8px; font-size: .85rem; color: var(--muted); }
    .labels .on { color: var(--paddy); font-weight: 600; }
    section { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 22px; }
    .grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 4px; }
    /* align-content: start keeps a field's label and box together when its neighbour has a hint and is taller. */
    .field { display: grid; gap: 4px; align-content: start; font-size: .93rem; font-weight: 600; }
    .field small { font-weight: 400; color: var(--muted); }
    input { font: inherit; font-weight: 400; padding: 10px 12px; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); color: var(--ink); width: 100%; }
    input:focus { outline: 3px solid color-mix(in oklab, var(--paddy) 35%, transparent); border-color: var(--paddy); }
    ul.checks { list-style: none; padding: 0; margin: 0 0 18px; display: grid; gap: 5px; }
    ul.checks li { display: flex; gap: 8px; align-items: baseline; }
    .ok { color: var(--paddy); } .bad { color: var(--bad); }
    .errors { border: 1px solid var(--bad); background: color-mix(in oklab, var(--bad) 8%, var(--card)); color: var(--bad); border-radius: 12px; padding: 12px 16px; margin-bottom: 14px; }
    .errors p { margin: 2px 0; }
    .actions { display: flex; gap: 10px; justify-content: space-between; margin-top: 20px; }
    button, a.button { font: inherit; font-weight: 600; border: 0; border-radius: 10px; padding: 11px 22px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .primary { background: var(--paddy); color: #fff; box-shadow: inset 0 -2px 0 rgb(0 0 0 / .15); margin-left: auto; }
    @media (prefers-color-scheme: dark) { .primary { color: #17200f; } }
    .secondary { background: transparent; color: var(--ink); border: 1px solid var(--line); }
    button:disabled { opacity: .5; cursor: not-allowed; }
    details { margin-top: 14px; border-top: 1px dashed var(--line); padding-top: 12px; }
    summary { cursor: pointer; color: var(--muted); }
    code { background: var(--bg); border: 1px solid var(--line); border-radius: 6px; padding: 1px 6px; font-size: .88em; }
    .done { text-align: center; }
    .done .badge { width: 64px; height: 64px; border-radius: 50%; background: color-mix(in oklab, var(--paddy) 15%, transparent); color: var(--paddy); display: grid; place-items: center; margin: 0 auto 10px; font-size: 32px; }
    .done ul.checks { text-align: left; }
    form.wait button.primary::after { content: ''; width: 14px; height: 14px; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>
<main>
    <header>
        <?= $logo ?>
        <div>
            <h1>মাটির খামার</h1>
            <div class="muted" style="margin:0">ইনস্টল — ধাপ <?= $step ?> / ৫</div>
        </div>
    </header>

    <div class="progress">
        <div class="bar"><i style="width: <?= $step * 20 ?>%"></i></div>
        <div class="labels"><?php foreach ($steps as $number => $label) { ?><span class="<?= $step >= $number ? 'on' : '' ?>"><?= $label ?></span><?php } ?></div>
    </div>

    <?php if ($errors !== []) { ?>
    <div class="errors"><?php foreach ($errors as $error) { ?><p>✗ <?= $e($error) ?></p><?php } ?></div>
    <?php } ?>

    <section>
    <?php if ($step === 1) { ?>
        <h2>সার্ভার পরীক্ষা</h2>
        <p class="muted">খামারের হিসাব চালাতে সার্ভারে যা যা লাগে, সব আছে কি না।</p>
        <ul class="checks">
        <?php foreach ($requirements as $check) { ?>
            <li><span class="<?= $check['ok'] ? 'ok' : 'bad' ?>"><?= $check['ok'] ? '✓' : '✗' ?></span><span><?= $e($check['label']) ?><?php if (! $check['ok']) { ?> — <span class="muted"><?= $e($check['help']) ?></span><?php } ?></span></li>
        <?php } ?>
        </ul>
        <form method="post" autocomplete="off">
            <?= $hidden ?>
            <?= $field('token', 'সেটআপ কোড', ['required' => true, 'hint' => 'hPanel → File Manager → <code>klokads_khamar_app/storage/app/install-token.txt</code> খুলে কপি করুন। এতে বোঝা যায় আপনিই সার্ভারের মালিক।']) ?>
            <div class="actions"><button class="primary" name="action" value="server" <?= $ready ? '' : 'disabled' ?>>পরের ধাপ: ডাটাবেস →</button></div>
        </form>

    <?php } elseif ($step === 2) { ?>
        <h2>সাইট ও ডাটাবেস</h2>
        <p class="muted">hPanel → Databases-এ যে ডাটাবেস খুলেছেন তার তথ্য। সংযোগ পরীক্ষা করেই পরের ধাপে যাবে।</p>
        <form method="post" autocomplete="off">
            <?= $hidden ?>
            <div class="grid">
                <?= $field('app_url', 'সাইটের ঠিকানা', ['required' => true, 'hint' => 'https:// সহ']) ?>
                <?= $field('db_host', 'ডাটাবেস হোস্ট', ['required' => true, 'hint' => 'Hostinger-এ localhost']) ?>
                <?= $field('db_name', 'ডাটাবেসের নাম', ['required' => true, 'placeholder' => 'u477584574_khamar']) ?>
                <?= $field('db_user', 'ডাটাবেস ইউজার', ['required' => true, 'placeholder' => 'u477584574_khamar']) ?>
                <?= $field('db_pass', 'ডাটাবেস পাসওয়ার্ড', ['type' => 'password', 'auto' => 'new-password', 'hint' => isset($_SESSION['in']['db_pass']) ? 'আগে দেওয়া আছে — বদলাতে চাইলেই লিখুন' : 'hPanel-এ ডাটাবেস খোলার সময় যেটা দিয়েছিলেন']) ?>
                <?= $field('db_port', 'পোর্ট', ['required' => true]) ?>
            </div>
            <details>
                <summary>ইমেইল পাঠানো (ঐচ্ছিক) — পাসওয়ার্ড ভুলে গেলে রিসেট লিংকের জন্য</summary>
                <div class="grid" style="margin-top:12px">
                    <?= $field('mail_from', 'Hostinger মেইল ঠিকানা', ['type' => 'email', 'placeholder' => 'khamar@klokads.com']) ?>
                    <?= $field('mail_pass', 'মেইলের পাসওয়ার্ড', ['type' => 'password', 'auto' => 'new-password']) ?>
                </div>
            </details>
            <div class="actions">
                <button class="secondary" name="action" value="back" formnovalidate>← আগের ধাপ</button>
                <button class="primary" name="action" value="database">সংযোগ পরীক্ষা করে এগোন →</button>
            </div>
        </form>

    <?php } elseif ($step === 3) { ?>
        <h2>ডাটাবেস তৈরি</h2>
        <p class="muted">সংযোগ ঠিক আছে (<?= $e($in['db_user']) ?>@<?= $e($in['db_host']) ?> / <?= $e($in['db_name']) ?>)। এখন টেবিল তৈরি হবে আর খামারের শুরুর সাজসজ্জা বসবে:</p>
        <ul class="checks">
            <li><span class="ok">•</span>হিসাব খাত (নগদ, ব্যাংক, বিকাশ, খরচ ও আয়ের খাত)</li>
            <li><span class="ok">•</span>ক্যাটাগরি: গাভী, বকনা, মোটাতাজাকরণ, বাছুর, ঘাস চাষ, দুগ্ধজাত পণ্য…</li>
            <li><span class="ok">•</span>উদ্যোগ বাজেটের ১২টি খাত</li>
            <li><span class="ok">•</span>স্টকের মাল: ভুসি, খড়, দানাদার, ঘাস, ঔষধ</li>
        </ul>
        <form method="post" onsubmit="this.classList.add('wait'); this.querySelector('.primary').disabled = true; this.querySelector('.primary').textContent = 'তৈরি হচ্ছে… ';">
            <?= $hidden ?>
            <input type="hidden" name="action" value="build">
            <div class="actions">
                <button class="secondary" name="action" value="back" formnovalidate>← আগের ধাপ</button>
                <button class="primary" type="submit">ডাটাবেস তৈরি করুন</button>
            </div>
        </form>

    <?php } elseif ($step === 4) { ?>
        <h2>হিসাব অ্যাডমিন (আপনি)</h2>
        <ul class="checks"><?php foreach ($_SESSION['build_log'] ?? [] as $line) { ?><li><span class="ok">✓</span><?= $e($line) ?></li><?php } ?></ul>
        <p class="muted">এই অ্যাকাউন্ট দিয়ে সব হিসাব, ব্যবহারকারী আর সেটিং চলবে। প্রথম লগইনে ২-ধাপ যাচাই চালু করতে হবে।</p>
        <form method="post" autocomplete="off">
            <?= $hidden ?>
            <div class="grid">
                <?= $field('admin_name', 'আপনার নাম', ['required' => true]) ?>
                <?= $field('admin_email', 'ইমেইল (লগইনের জন্য)', ['type' => 'email', 'required' => true, 'auto' => 'username']) ?>
                <?= $field('admin_password', 'পাসওয়ার্ড', ['type' => 'password', 'required' => true, 'auto' => 'new-password', 'hint' => 'কমপক্ষে ১২ অক্ষর, ছোট-বড় হাতের অক্ষর ও সংখ্যা']) ?>
                <?= $field('admin_password_again', 'পাসওয়ার্ড আবার', ['type' => 'password', 'required' => true, 'auto' => 'new-password']) ?>
            </div>
            <div class="actions"><button class="primary" name="action" value="admin">অ্যাডমিন তৈরি করে শেষ করুন →</button></div>
        </form>

    <?php } else { ?>
        <div class="done">
            <div class="badge">✓</div>
            <h2>ইনস্টল সম্পূর্ণ</h2>
            <p class="muted">মাটির খামারের হিসাব এখন চালু। ইনস্টলার নিজে বন্ধ হয়ে গেছে।</p>
            <ul class="checks"><?php foreach ($finished['log'] as $line) { ?><li><span class="ok">✓</span><?= $e($line) ?></li><?php } ?></ul>
            <p class="muted">লগইন করুন <b><?= $e($finished['email']) ?></b> দিয়ে। প্রথমবার Google Authenticator দিয়ে ২-ধাপ যাচাই চালু করতে বলবে।</p>
            <a class="button primary" style="margin:0 auto" href="/login">লগইন পাতায় যান →</a>
        </div>
        <?php session_destroy(); ?>
    <?php } ?>
    </section>
</main>
</body>
</html>
