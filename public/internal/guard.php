<?php

declare(strict_types=1);

/**
 * Shared start-up for the internal pages.
 *
 * These exist only where a 'dev' environment is configured, so a deploy or a
 * clone without dev credentials simply does not have them.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

if (! $config->hasEnvironment('dev')) {
    http_response_code(404);
    exit('Not found');
}
