<?php

declare(strict_types=1);

/**
 * 6. FPX DIRECT DEBIT
 *
 * For recurring collections -- subscriptions, instalments, monthly fees. The
 * payer authorises you once, and you collect on a schedule afterwards.
 *
 * The important difference from a one-off payment is that there are two
 * separate things to track:
 *
 *   the mandate      the payer's standing authorisation. Enrol once.
 *   the transactions each individual collection made under that mandate.
 *
 * A mandate has its own lifecycle and its own callbacks:
 *
 *   enrolment    the payer applies
 *   bank approval    their bank approves or rejects the mandate
 *   authorisation    the mandate becomes active
 *   maintenance      change the amount or frequency later
 *   termination      cancel it
 *
 * Each of those callbacks has its own verify method -- listed at the bottom of
 * this file. Using verifyTransactionCallbackData() on a mandate callback will
 * reject a perfectly valid one.
 *
 * Run:  php guide/06-direct-debit.php
 */

require __DIR__ . '/_bootstrap.php';

use Bayarcash\FpxDirectDebit;

// Enrolment: ask the payer to authorise future collections.
//
//   application_type   01 = new enrolment, 02 = maintenance, 03 = termination
//   frequency_mode     DL daily, WK weekly, MT monthly, YR yearly
//   effective_date     when collections may start
//   expiry_date        when the authorisation lapses
//   amount             the ceiling per collection, not a charge
$enrolment = [
    'portal_key'              => $portalKey,
    'order_number'            => 'DD' . date('ymdHis'),
    'amount'                  => '50.00',
    'payer_name'              => 'John Doe',
    'payer_email'             => 'john.doe@example.com',
    'payer_telephone_number'  => '60123456789',
    'payer_id_type'           => '1',            // 1 = NRIC
    'payer_id_value'          => '900101015500',
    'application_type'        => '01',
    'frequency_mode'          => 'MT',
    'effective_date'          => date('Y-m-d'),
    'expiry_date'             => date('Y-m-d', strtotime('+1 year')),
    'callback_url'            => $config->url('callback.php'),
    'return_url'              => $config->url('return.php'),
];

out('Enrolment request', $enrolment);

// Sending it returns a URL to redirect the payer to, exactly like a payment:
//
//     $mandate = $bayarcash->createFpxDirectDebitEnrollment($enrolment);
//     header('Location: ' . $mandate->url);
//
// Uncomment to create a real mandate in your configured environment.
out('Not sent', "Uncomment the call in this file to enrol for real.\n");

// The SDK turns the numeric mandate codes into readable text.
out('Status labels', [
    'getStatusText(0)'        => FpxDirectDebit::getStatusText(0),
    'getStatusText(1)'        => FpxDirectDebit::getStatusText(1),
    'getApplicationTypeText'  => FpxDirectDebit::getApplicationTypeText('01'),
    'getFrequencyModeText'    => FpxDirectDebit::getFrequencyModeText('MT'),
]);

// Managing a mandate afterwards:
//
//   createFpxDirectDebitMaintenance($mandateId, $data)   change amount/frequency
//   createFpxDirectDebitTermination($mandateId, $data)   cancel it
//   getFpxDirectDebit($id)                               fetch the mandate
//   getFpxDirectDebitTransaction($id)                    fetch one collection
//
// Verifying mandate callbacks -- match the method to the callback:
//
//   verifyDirectDebitBankApprovalCallbackData()    bank approved/rejected
//   verifyDirectDebitAuthorizationCallbackData()   mandate became active
//   verifyDirectDebitTransactionCallbackData()     a collection was attempted
