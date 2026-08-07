<?php

declare(strict_types=1);

/**
 * 4. LOOKING UP TRANSACTIONS
 *
 * Callbacks tell you about payments as they happen. These methods let you ask
 * about them afterwards -- for a support screen, a reconciliation job, or to
 * recover a status you missed because your server was down.
 *
 * Most of them require API v3:
 *
 *     $bayarcash->setApiVersion('v3');
 *
 * On v2 they throw. BayarcashFactory sets it from config['api_version'].
 *
 * Querying is a safety net, not a substitute for handling callbacks. Polling
 * every order is slow and hits rate limits; the callback is how you find out.
 *
 * Run:  php guide/04-query-transaction.php
 */

require __DIR__ . '/_bootstrap.php';

use Bayarcash\Exceptions\NotFoundException;
use BayarcashDemo\PaymentStatus;

out('API version', $config->apiVersion());

// The most useful lookup: your own order reference, which you set when you
// created the payment intent.
try {
    $orderNumber = 'GUIDE260807';
    out("getTransactionByOrderNumber({$orderNumber})", $bayarcash->getTransactionByOrderNumber($orderNumber));
} catch (NotFoundException $e) {
    out("getTransactionByOrderNumber({$orderNumber})", 'Not found -- expected unless that order exists.');
}

// Everything, newest first. Accepts filters such as page and per_page.
$all = $bayarcash->getAllTransactions(['per_page' => 5]);
out('getAllTransactions(per_page: 5)', $all);

// Filter by status. The codes are the same ones callbacks carry:
//
//   0 New   1 Pending   2 Unsuccessful   3 Successful   4 Cancelled   5 Expired
//
// Only 3 means you have been paid.
out(
    'getTransactionsByStatus(3 = Successful)',
    $bayarcash->getTransactionsByStatus((string) PaymentStatus::SUCCESSFUL)
);

// Other lookups available on v3:
//
//   getTransaction($id)                       by Bayarcash transaction id
//   getTransactionsByPayerEmail($email)       everything one payer has paid
//   getTransactionsByPaymentChannel($channel) by channel id
//   getTransactionByReferenceNumber($ref)     by FPX exchange reference
//   getPaymentIntent($id)                     the intent, before payment
//   cancelPaymentIntent($id)                  abandon an unpaid intent
