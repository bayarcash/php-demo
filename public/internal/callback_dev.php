<?php

declare(strict_types=1);

/**
 * Callback receiver for the internal environment.
 *
 * Not behind guard.php: a server posts here and cannot log in. It is the
 * checksum that authenticates the message, the same as public/callback.php.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use BayarcashDemo\BayarcashFactory;
use BayarcashDemo\Log;

header('Content-Type: text/plain; charset=utf-8');

if (! $config->hasEnvironment('dev')) {
    http_response_code(404);
    exit('Not found');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only.');
}

$callback = $_POST;
Log::write('[dev] Callback received', $callback);

try {
    $bayarcash  = BayarcashFactory::make($config, 'dev');
    $secretKey  = $config->credentials('dev')['secret_key'] ?? '';
    $recordType = (string) ($callback['record_type'] ?? '');

    $verified = match ($recordType) {
        'pre_transaction' => $bayarcash->verifyPreTransactionCallbackData($callback, $secretKey),
        'transaction', 'transaction_receipt' => $bayarcash->verifyTransactionCallbackData($callback, $secretKey),
        default => false,
    };

    if (! $verified) {
        Log::write('[dev] Callback rejected', ['record_type' => $recordType]);

        http_response_code(400);
        exit('checksum mismatch');
    }

    Log::write('[dev] Callback verified', [
        'record_type'    => $recordType,
        'transaction_id' => $callback['transaction_id'] ?? null,
        'status'         => $callback['status'] ?? null,
    ]);

    http_response_code(200);
    echo 'OK';
} catch (Throwable $e) {
    Log::write('[dev] Callback failed: ' . $e->getMessage());

    http_response_code(500);
    echo 'error';
}
