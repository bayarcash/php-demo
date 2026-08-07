<?php

declare(strict_types=1);

/**
 * Where the payer's browser lands after paying.
 *
 * Both this page and callback.php record status, and between them they cover
 * each other:
 *
 *   callback.php  always fires, even if the payer closes the browser, but can
 *                 be delayed or blocked by a deploy or firewall.
 *   return.php    instant when the payer does come back, but never fires if
 *                 they close the tab at the bank.
 *
 * Writing from both is safe only because they share one guarded write --
 * TransactionRepository::recordCallback() is idempotent on transaction_id and
 * refuses to move a payment that already reached a final status. Whichever
 * arrives second is ignored rather than overwriting the first.
 *
 * The checksum is verified before recording, exactly as in callback.php.
 * Unverified data is never written.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/_layout.php';

use BayarcashDemo\BayarcashFactory;
use BayarcashDemo\Db;
use BayarcashDemo\Log;
use BayarcashDemo\PaymentStatus;
use BayarcashDemo\TransactionRepository;

$returned = $_GET;
$verified = false;
$record   = null;

if ($returned !== []) {
    Log::write('Return URL received', $returned);

    $bayarcash = BayarcashFactory::make($config);

    $verified = $bayarcash->verifyReturnUrlCallbackData(
        $returned,
        $config->credentials()['secret_key'] ?? ''
    );

    $repository = new TransactionRepository(Db::fromConfig($config));

    // Record only what the checksum vouched for. If the callback already
    // settled this payment the guard ignores us; if it has not arrived yet,
    // the payer sees the right status immediately instead of "pending".
    if ($verified) {
        $outcome = $repository->recordCallback($returned, 'return');
        Log::write("Return URL {$outcome}", ['order_number' => $returned['order_number'] ?? null]);
    }

    $record = ! empty($returned['transaction_id'])
        ? $repository->find((string) $returned['transaction_id'])
        : $repository->findByOrderNumber((string) ($returned['order_number'] ?? ''));
}

$status = $record['status'] ?? null;

layout_head('Payment result', $config, '');
?>

<?php if ($returned === []): ?>
    <div class="notice">
        Nothing to show. Start a payment from <a href="index.php">Checkout</a>.
    </div>

<?php elseif (! $verified): ?>
    <div class="verdict" data-tone="danger">
        <div class="verdict-eyebrow">Return URL</div>
        <h2>Could not verify this result</h2>
        <p>
            The checksum did not match, so nothing on this page should be trusted
            or acted on. A mismatch means the values were altered in transit, or
            were never signed by Bayarcash at all.
        </p>
    </div>

<?php else: ?>
    <div class="verdict" data-tone="<?= e(PaymentStatus::tone($status)) ?>">
        <div class="verdict-top">
            <h2><?= e(PaymentStatus::label($status)) ?></h2>
            <?php if ($record !== null): ?>
                <span class="verdict-sum">
                    <strong><?= e($record['currency'] ?: 'MYR') ?> <?= e(number_format((float) $record['amount'], 2)) ?></strong>
                    <span class="sep">·</span><?= e($record['order_number']) ?>
                </span>
            <?php endif; ?>
        </div>

        <p class="verdict-note">
            <span class="status" data-tone="<?= e(PaymentStatus::tone($status)) ?>">Checksum verified</span>
            <span class="sep">·</span>
            <?php
            // Point at what is actually on this page and non-obvious: the two
            // payloads differ, and the return URL is the thinner of the two.
            echo $record === null
                ? 'No transaction ID in this result, so nothing could be keyed on it.'
                : 'Compare the payloads below — the return URL carries fewer fields.';
            ?>
        </p>
    </div>

    <?php if ($record !== null): ?>
        <div class="panel">
            <div class="panel-head">
                <span>Transaction</span>
                <span class="status" data-tone="<?= e(PaymentStatus::tone($status)) ?>">
                    <?= e(PaymentStatus::label($status)) ?>
                </span>
            </div>
            <div class="table-wrap">
                <table class="detail">
                    <tbody>
                        <?php
                        // The return URL carries fewer fields than the transaction
                        // callback does -- payer name and email arrive only on the
                        // callback. Rows with nothing in them are skipped rather
                        // than rendered blank.
                        $rows = [
                            'Order number'      => $record['order_number'],
                            'Transaction ID'    => $record['transaction_id'],
                            'Amount'            => $record['amount'] === ''
                                ? ''
                                : ($record['currency'] ?: 'MYR') . ' ' . number_format((float) $record['amount'], 2),
                            'Payer'             => $record['payer_name'],
                            'Email'             => $record['payer_email'],
                            'Bank'              => $record['payer_bank_name'],
                            'Exchange reference'=> $record['exchange_reference_number'],
                            // ISO 8601 carries its own offset -- keep it, or the
                            // time silently shifts into the server's timezone.
                            'Paid at'           => $record['datetime'] === ''
                                ? ''
                                : (($d = date_create_immutable($record['datetime']))
                                    ? $d->format('j M Y, g:i a (P)')
                                    : $record['datetime']),
                            'Description'       => $record['status_description'],
                        ];

                        foreach ($rows as $label => $value):
                            if ($value === null || $value === '') {
                                continue;
                            }
                        ?>
                            <tr>
                                <td><?= e($label) ?></td>
                                <td class="num"><?= e($value) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        $payloads = [
            'Received by return.php' => [$record['raw_return'] ?? '', 'browser redirect, GET'],
            'Received by callback.php' => [$record['raw_callback'] ?? '', 'server to server, POST'],
        ];
        ?>
        <?php foreach ($payloads as $title => [$raw, $how]): ?>
            <div class="panel">
                <div class="panel-head">
                    <span><?= e($title) ?></span>
                    <span class="meta"><?= e($how) ?></span>
                </div>
                <div class="panel-body">
                    <?php if ($raw === '' || $raw === null): ?>
                        <div class="empty" style="padding:1rem 0">
                            Nothing received here yet.
                        </div>
                    <?php else: ?>
                        <pre class="code"><?= e($raw) ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<div class="btn-row">
    <a class="btn btn-primary" href="index.php">← New payment</a>
<a class="btn" href="orders.php">Transactions</a>
</div>

<?php layout_foot($config); ?>
