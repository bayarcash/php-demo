<?php

declare(strict_types=1);

/**
 * 2. PAYMENT CHANNELS
 *
 * `payment_channel` on a payment intent is a numeric ID. The SDK has a constant
 * for each, but which ones you may actually use depends on the portal: channels
 * are enabled per portal in the Bayarcash console, and sending a disabled one
 * fails with a ValidationException.
 *
 * So build your checkout options from getChannels(), not from a hardcoded list.
 *
 * Run:  php guide/02-payment-channels.php
 */

require __DIR__ . '/_bootstrap.php';

use Bayarcash\Bayarcash;

// Every channel the SDK knows about. Useful as a reference; not a promise that
// your portal accepts them.
out('Channel constants', [
    'FPX'                => Bayarcash::FPX,
    'MANUAL_TRANSFER'    => Bayarcash::MANUAL_TRANSFER,
    'FPX_DIRECT_DEBIT'   => Bayarcash::FPX_DIRECT_DEBIT,
    'FPX_LINE_OF_CREDIT' => Bayarcash::FPX_LINE_OF_CREDIT,
    'DUITNOW_DOBW'       => Bayarcash::DUITNOW_DOBW,
    'DUITNOW_QR'         => Bayarcash::DUITNOW_QR,
    'SPAYLATER'          => Bayarcash::SPAYLATER,
    'BOOST_PAYFLEX'      => Bayarcash::BOOST_PAYFLEX,
    'QRISOB'             => Bayarcash::QRISOB,
    'QRISWALLET'         => Bayarcash::QRISWALLET,
    'NETS'               => Bayarcash::NETS,
    'CREDIT_CARD'        => Bayarcash::CREDIT_CARD,
    'ALIPAY'             => Bayarcash::ALIPAY,
    'WECHATPAY'          => Bayarcash::WECHATPAY,
    'PROMPTPAY'          => Bayarcash::PROMPTPAY,
    'TOUCH_N_GO'         => Bayarcash::TOUCH_N_GO,
    'BOOST_WALLET'       => Bayarcash::BOOST_WALLET,
    'GRABPAY'            => Bayarcash::GRABPAY,
    'GRABPL'             => Bayarcash::GRABPL,
    'SHOPEE_PAY'         => Bayarcash::SHOPEE_PAY,
]);

// Your portals, if you manage more than one.
//
// Note the property names: the SDK camelCases what the API returns, so the
// JSON field `portal_key` is read as `$portal->portalKey`.
out('Portals', array_map(
    static fn ($portal) => [
        'portalKey'  => $portal->portalKey ?? null,
        'portalName' => $portal->portalName ?? null,
    ],
    $bayarcash->getPortals()
));

// What this portal will actually accept. Render your checkout from this.
out("Channels enabled on portal {$portalKey}", $bayarcash->getChannels($portalKey));

// FPX pays from a bank account, so you may let the payer pick their bank up
// front and pass it as `fpx_buyer_bank_code` on the payment intent. Skip it and
// Bayarcash asks them to choose on its own page, which is usually simpler.
out('FPX banks', $bayarcash->fpxBanksList());
