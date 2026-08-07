<?php

declare(strict_types=1);

/**
 * Shared start-up for every page. Requiring this gives you $config, and
 * autoloading for both the SDK and this demo's own src/ classes.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use BayarcashDemo\Config;

try {
    $config = Config::load();
} catch (RuntimeException $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit($e->getMessage());
}

date_default_timezone_set($config->timezone());

/** Escape for HTML output. Short name because views use it constantly. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** The SDK version actually installed, read from composer.lock. */
function sdk_version(): string
{
    static $version = null;

    if ($version !== null) {
        return $version;
    }

    $version = 'unknown';
    $lock = dirname(__DIR__) . '/composer.lock';

    if (is_file($lock)) {
        $data = json_decode((string) file_get_contents($lock), true);

        foreach ($data['packages'] ?? [] as $package) {
            if (($package['name'] ?? '') === 'bayarcash/php-sdk') {
                $version = ltrim((string) $package['version'], 'v');
                break;
            }
        }
    }

    return $version;
}
