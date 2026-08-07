<?php

declare(strict_types=1);

namespace BayarcashDemo;

use Bayarcash\Bayarcash;
use GuzzleHttp\Client;

/**
 * Builds a configured SDK instance -- the only place environment logic lives.
 */
final class BayarcashFactory
{
    public static function make(Config $config, ?string $environment = null): Bayarcash
    {
        $environment ??= $config->environment();
        $credentials = $config->credentials($environment);
        $token       = $credentials['bearer_token'] ?? '';

        $bayarcash = new Bayarcash($token);
        $bayarcash->setApiVersion($config->apiVersion());

        // A custom host (e.g. an internal API) is supported by handing the SDK
        // its own Guzzle client, which it then uses as-is.
        if (! empty($credentials['base_uri'])) {
            $bayarcash->setToken($token, new Client([
                'base_uri'    => rtrim($credentials['base_uri'], '/') . '/',
                'http_errors' => false,
                'headers'     => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
            ]));

            return $bayarcash;
        }

        if ($environment !== 'production') {
            $bayarcash->useSandbox();
        }

        return $bayarcash;
    }
}
