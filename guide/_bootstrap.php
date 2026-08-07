<?php

declare(strict_types=1);

/**
 * Shared setup for the guide scripts. Every numbered example requires this
 * first, so each one can stay focused on the single SDK call it demonstrates.
 *
 * Run any of them from the project root:
 *
 *     php guide/01-create-payment-intent.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use BayarcashDemo\BayarcashFactory;
use BayarcashDemo\Config;

$config      = Config::load();
$bayarcash   = BayarcashFactory::make($config);
$credentials = $config->credentials();

$portalKey = $credentials['portal_key'] ?? '';
$secretKey = $credentials['secret_key'] ?? '';

function out(string $heading, mixed $value = null): void
{
    echo "\n", $heading, "\n", str_repeat('-', strlen($heading)), "\n";

    if ($value !== null) {
        echo is_string($value)
            ? $value
            : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n";
    }
}
