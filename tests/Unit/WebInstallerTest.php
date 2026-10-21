<?php

use App\Support\Install\WebInstaller;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/khamar-install-'.bin2hex(random_bytes(4));
    mkdir($this->dir.'/storage/app', 0777, true);
    mkdir($this->dir.'/deploy/hostinger', 0777, true);
    copy(dirname(__DIR__, 2).'/deploy/hostinger/env.example', $this->dir.'/deploy/hostinger/env.example');
    $this->installer = new WebInstaller($this->dir);
});

afterEach(function () {
    exec(PHP_OS_FAMILY === 'Windows' ? 'rmdir /s /q '.escapeshellarg($this->dir) : 'rm -rf '.escapeshellarg($this->dir));
});

it('stays open on a fresh server and closes once a real .env exists', function () {
    expect($this->installer->closed())->toBeFalse();

    file_put_contents($this->dir.'/.env', "APP_KEY=base64:abc\n");
    expect($this->installer->closed())->toBeTrue();
});

it('closes for good after a finished install', function () {
    file_put_contents($this->dir.'/storage/app/installed', '{}');

    expect($this->installer->closed())->toBeTrue();
});

it('only accepts the setup code from the server file', function () {
    $code = $this->installer->token();

    expect($this->installer->tokenMatches($code))->toBeTrue()
        ->and($this->installer->tokenMatches(''))->toBeFalse()
        ->and($this->installer->tokenMatches('guess'))->toBeFalse();
});

it('writes passwords with quotes, spaces and dollar signs safely into .env', function () {
    $env = $this->installer->envContents([
        'app_url' => 'https://khamar.klokads.com/',
        'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => 'u477584574_khamar', 'db_user' => 'u477584574_khamar',
        'db_pass' => 'pa"ss $word#1', 'mail_from' => '', 'mail_pass' => '',
    ]);

    expect($env)->toContain('APP_URL=https://khamar.klokads.com'."\n")
        ->and($env)->toContain('DB_PASSWORD="pa\\"ss \\$word#1"')
        ->and($env)->toContain('DB_DATABASE=u477584574_khamar')
        ->and($env)->toMatch('/^APP_KEY=base64:\S+$/m')
        ->and($env)->toContain('KHAMAR_REQUIRE_2FA=true')
        ->and($env)->toContain('KHAMAR_INSTALLING=true');
});
