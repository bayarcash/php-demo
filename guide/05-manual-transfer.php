<?php

declare(strict_types=1);

/**
 * 5. MANUAL BANK TRANSFER
 *
 * For payers who transfer money themselves and upload proof, instead of paying
 * through an online channel. The flow differs from a normal payment:
 *
 *   1. You submit the transfer with the payer's uploaded receipt.
 *   2. Nothing is confirmed. Someone has to check the money actually arrived.
 *   3. You (or Bayarcash) set the final status once it has been checked.
 *
 * Because step 3 is a human decision, never treat a submitted manual transfer
 * as paid. It is a claim until somebody confirms it.
 *
 * Run:  php guide/05-manual-transfer.php
 */

require __DIR__ . '/_bootstrap.php';

use Bayarcash\Bayarcash;

// A manual transfer is created as a payment intent on the MANUAL_TRANSFER
// channel, plus the payer's proof of payment.
$data = [
    'portal_key'             => $portalKey,
    'order_number'           => 'MANUAL' . date('ymdHis'),
    'amount'                 => '10.00',
    'description'            => 'Manual transfer example',
    'payer_name'             => 'John Doe',
    'payer_email'            => 'john.doe@example.com',
    'payer_telephone_number' => '60123456789',
    'payment_channel'        => Bayarcash::MANUAL_TRANSFER,
    'callback_url'           => $config->url('callback.php'),
    'return_url'             => $config->url('return.php'),
];

$data['checksum'] = $bayarcash->createPaymentIntenChecksumValue($secretKey, $data);

out('Request', $data);

// createManualBankTransfer() submits it. The second argument controls whether
// the SDK follows Bayarcash's redirect; false keeps the response for you to
// inspect, which is what you want on a server.
//
//     $response = $bayarcash->createManualBankTransfer($data, false);
//
// Uncomment to actually send. It creates a real record in whichever
// environment your config points at.
out('Not sent', "Uncomment the call in this file to submit a real transfer.\n");

// Once someone has verified the money arrived, set the outcome:
//
//     $bayarcash->updateManualBankTransferStatus($refNo, $status, $amount);
//
// $status uses the same codes as everywhere else:
//
//   2 Unsuccessful   3 Successful   4 Cancelled   5 Expired
//
// Confirming payment is what 3 means here. Do not send it until the funds are
// confirmed in your bank account -- there is no gateway checking this for you.
out('Confirming later', 'updateManualBankTransferStatus($refNo, "3", "10.00")');
