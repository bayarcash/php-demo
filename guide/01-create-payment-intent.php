<?php

declare(strict_types=1);

/**
 * 1. CREATING A PAYMENT
 *
 * A payment intent is the record Bayarcash creates before the payer has paid
 * anything. You build it, sign it, and get back a URL to send the payer to.
 *
 * Run:  php guide/01-create-payment-intent.php
 */

require __DIR__ . '/_bootstrap.php';

use Bayarcash\Bayarcash;
use Bayarcash\Exceptions\ValidationException;

// The request body. Everything here except `checksum` is your data.
//
//   portal_key   which of your portals collects this payment
//   order_number YOUR reference. It comes back on every callback, so make it
//                unique per order -- this is how you match a payment to what
//                the customer bought.
//   amount       a decimal string, e.g. '10.00'. Portals set a minimum.
//   payment_channel  see 02-payment-channels.php for the list.
//   callback_url server-to-server, authoritative, always fires.
//   return_url   where the payer's browser lands. May never be reached.
$data = [
    'portal_key'             => $portalKey,
    'order_number'           => 'GUIDE' . date('ymdHis'),
    'amount'                 => '10.00',
    'description'            => 'Guide example payment',
    'payer_name'             => 'John Doe',
    'payer_email'            => 'john.doe@example.com',
    'payer_telephone_number' => '60123456789',
    'payment_channel'        => Bayarcash::FPX,
    'callback_url'           => $config->url('callback.php'),
    'return_url'             => $config->url('return.php'),
];

// Sign the finished array:
//
//   - the SDK sorts the fields, then HMACs them with your API secret key
//   - Bayarcash recomputes it from what arrives and rejects any mismatch
//   - this is what stops anyone editing the amount in transit
//
// Sign last. Any field added or changed after this line invalidates it.
$data['checksum'] = $bayarcash->createPaymentIntenChecksumValue($secretKey, $data);

out('Request', $data);

try {
    $paymentIntent = $bayarcash->createPaymentIntent($data);

    // The SDK camelCases what the API returns, so the JSON field
    // `order_number` is read back as `$paymentIntent->orderNumber`.
    out('Response', [
        'id'          => $paymentIntent->id ?? null,
        'url'         => $paymentIntent->url ?? null,
        'orderNumber' => $paymentIntent->orderNumber ?? null,
    ]);

    // In a web app this is where you would redirect:
    //
    //     header('Location: ' . $paymentIntent->url);
    //     exit;
    //
    // Do not mark the order paid here. Nothing has been paid yet -- wait for
    // the callback. See 03-verify-callback.php.
    out('Next step', 'Send the payer to: ' . ($paymentIntent->url ?? 'n/a'));
} catch (ValidationException $e) {
    // Bayarcash rejected the body: bad phone format, amount below the portal
    // minimum, or a channel this portal has not enabled.
    out('Rejected', $e->errors());
}
