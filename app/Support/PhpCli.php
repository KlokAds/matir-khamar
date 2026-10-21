<?php

namespace App\Support;

/**
 * The command-line php matching the one serving this request. Hostinger serves PHP through lsphp,
 * with the plain `php` binary in the same version folder (e.g. /opt/alt/php85/usr/bin), and child
 * processes must not be started with the web binary.
 */
final class PhpCli
{
    public static function path(): string
    {
        if (filled($configured = config('khamar.php_cli'))) {
            return $configured;
        }

        $binary = PHP_BINARY;

        if ($binary === '') {
            return 'php';
        }

        if (preg_match('/^php(\d[\d.]*)?(\.exe)?$/', basename($binary))) {
            return $binary;
        }

        // CloudLinux alt-php (Hostinger): the same version's CLI, not the server's default php.
        $alt = '/opt/alt/php'.PHP_MAJOR_VERSION.PHP_MINOR_VERSION.'/usr/bin/php';

        if (is_file($alt) && is_executable($alt)) {
            return $alt;
        }

        $sibling = dirname($binary).'/php';

        return is_file($sibling) && is_executable($sibling) ? $sibling : 'php';
    }
}
