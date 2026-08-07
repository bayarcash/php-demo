<?php

declare(strict_types=1);

/**
 * CALLBACK HANDLER -- the URL you pass as `callback_url` on the payment intent.
 *
 * Bayarcash POSTs here from its own servers as the payment progresses. You will
 * receive more than one callback for a single payment, identified by
 * `record_type`:
 *
 *   pre_transaction     the payer picked a bank and was sent to it. No money yet.
 *   transaction         the outcome. `status` is what you act on.
 *   transaction_receipt the settled receipt, sent after `transaction`.
 *
 * Each record_type has its own verification method, because each is signed over
 * a different set of fields. Using the wrong one will fail valid callbacks.
 *
 * This is the authoritative source of payment status -- not return.php. It
 * arrives even when the payer closes the browser mid-payment, and Bayarcash
 * retries it until it receives a 200 from you.
 */

require_once __DIR__ . '/bootstrap.php';

use BayarcashDemo\BayarcashFactory;
use BayarcashDemo\Db;
use BayarcashDemo\Log;
use BayarcashDemo\TransactionRepository;

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only.');
}

$callback = $_POST;
Log::write('Callback received', $callback);

try {
    $bayarcash  = BayarcashFactory::make($config);
    $secretKey  = $config->credentials()['secret_key'] ?? '';
    $recordType = (string) ($callback['record_type'] ?? '');

    // Verify before anything else.
    //
    // This URL is public. The checksum -- an HMAC over the payload using your
    // API secret key -- is the only proof the POST really came from Bayarcash.
    //
    // Never make this conditional on an environment, an order number prefix,
    // or a debug flag. Any condition you can satisfy, an attacker can too.
    $verified = match ($recordType) {
        'pre_transaction' => $bayarcash->verifyPreTransactionCallbackData($callback, $secretKey),
        'transaction', 'transaction_receipt' => $bayarcash->verifyTransactionCallbackData($callback, $secretKey),
        default => false,
    };

    if (! $verified) {
        Log::write('Callback rejected', ['record_type' => $recordType]);

        http_response_code(400);
        exit('checksum mismatch');
    }

    // Persist against `transaction_id`, which is stable for the whole payment.
    //
    // The write must tolerate two things:
    //
    //   - being repeated    delivery is at-least-once
    //   - arriving late     callbacks can overtake each other
    //
    // See TransactionRepository::recordCallback().
    $repository = new TransactionRepository(Db::fromConfig($config));
    Log::write('Callback accepted: ' . $repository->recordCallback($callback, 'callback'));

    // Answer 200 as soon as the payment is recorded. Bayarcash treats a slow or
    // non-200 response as a failure and schedules a retry, so do fulfilment
    // work -- emails, invoices, shipping -- from a queue or cron reading the
    // stored status, not inline here.
    http_response_code(200);
    echo 'OK';
} catch (Throwable $e) {
    // Deliberately 500, not 200. A 500 asks Bayarcash to deliver this callback
    // again; swallowing the error with a 200 would tell it the payment was
    // handled and you would never hear about it again.
    Log::write('Callback failed: ' . $e->getMessage());

    http_response_code(500);
    echo 'error';
}
