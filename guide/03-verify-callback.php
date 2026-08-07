<?php

declare(strict_types=1);

/**
 * 3. VERIFYING A CALLBACK  <-- the most important file in this guide
 *
 * Bayarcash POSTs to your `callback_url` as the payment progresses. That URL is
 * public: anyone can send it a POST claiming a payment succeeded. The checksum
 * on the payload is the only thing that distinguishes a real callback from a
 * forged one, so verification is not optional and must never be conditional.
 *
 * You will receive several callbacks per payment, told apart by `record_type`:
 *
 *   pre_transaction      payer was sent to their bank. No money has moved.
 *   transaction          the outcome. `status` is what you act on.
 *   transaction_receipt  settled receipt, follows `transaction`.
 *
 * Each is signed over different fields and therefore has its own verify method.
 * Using the wrong one rejects valid callbacks.
 *
 * Run:  php guide/03-verify-callback.php
 */

require __DIR__ . '/_bootstrap.php';

// A callback body looks like this. In real code it is $_POST.
$callback = [
    'record_type'               => 'transaction',
    'transaction_id'            => 'trx_ABC123',
    'exchange_reference_number' => '2026080712345678',
    'order_number'              => 'GUIDE260807',
    'currency'                  => 'MYR',
    'amount'                    => '10.00',
    'payer_name'                => 'John Doe',
    'payer_email'               => 'john.doe@example.com',
    'status'                    => '3',
    'status_description'        => 'Approved',
    'datetime'                  => '2026-08-07 12:00:00',
    'checksum'                  => 'not-a-real-checksum',
];

// Pick the verifier by record_type.
$verified = match ($callback['record_type']) {
    'pre_transaction' => $bayarcash->verifyPreTransactionCallbackData($callback, $secretKey),
    'transaction', 'transaction_receipt' => $bayarcash->verifyTransactionCallbackData($callback, $secretKey),
    default => false,
};

out('Verified', var_export($verified, true));

// false here is expected: the checksum above is fake. A real callback from
// Bayarcash returns true.
if (! $verified) {
    out('Rejected', "Respond 400 and stop. Do not record anything.\n");
    exit;
}

// Only past this line may you trust the contents.
//
// Two rules govern what you do next. Both come from how delivery works:
//
//   1. Delivery is at-least-once.
//      Bayarcash retries until you answer 200, so the same callback arrives
//      more than once. Key your write on transaction_id and update in place.
//      Never blindly insert.
//
//   2. Delivery can be out of order.
//      A retried pre_transaction can land after the transaction that settled
//      the payment. Treat 2, 3, 4 and 5 as final and refuse to leave them.
//
// public/callback.php and src/TransactionRepository.php implement exactly this.
out('Accepted', 'status ' . $callback['status'] . ' -- record it, then answer 200.');

// Status codes:
//   0 New   1 Pending   2 Unsuccessful   3 Successful   4 Cancelled   5 Expired
// Only 3 means you have been paid.
