<?php

declare(strict_types=1);

/** Everything recorded so far, newest first. */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/_layout.php';

use BayarcashDemo\Db;
use BayarcashDemo\Log;
use BayarcashDemo\PaymentStatus;
use BayarcashDemo\TransactionRepository;

// Clearing the log is a write, so it only happens on POST and redirects
// afterwards -- a refresh must not repeat it.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log'])) {
    Log::clear();

    header('Location: orders.php');
    exit;
}

$db           = Db::fromConfig($config);
$repository   = new TransactionRepository($db);
$transactions = $repository->all();
$recentLog    = Log::tail(20);

$settled   = array_filter($transactions, fn ($t) => PaymentStatus::isPaid($t['status']));
$collected = array_sum(array_map(fn ($t) => (float) $t['amount'], $settled));

// Count by status so the summary shows where the rest ended up, not just
// what succeeded -- unsuccessful and pending are the interesting ones.
$byStatus = [];
foreach ($transactions as $t) {
    $label = PaymentStatus::label($t['status']);
    $byStatus[$label] = ($byStatus[$label] ?? 0) + 1;
}
arsort($byStatus);

layout_head('Transactions', $config, 'orders.php');
?>

<div class="panel">
    <div class="panel-head">
        <span>Transactions</span>
        <?php env_badge($config); ?>
    </div>

    <?php if ($transactions === []): ?>
        <div class="empty">
            Nothing recorded yet.<br>
            Complete a payment and it will appear here.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Transaction</th>
                        <th>Intent</th>
                        <th style="text-align:right">Amount</th>
                        <th>Status</th>
                        <th>Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $row): ?>
                        <tr>
                            <td><?= e($row['order_number']) ?></td>
                            <td class="id">
                                <a href="api-console.php?operation=transaction&amp;id=<?= e(urlencode($row['transaction_id'])) ?>"
                                   title="Look up this transaction in the API console">
                                    <?= e($row['transaction_id']) ?>
                                </a>
                            </td>
                            <td class="id">
                                <?php if (empty($row['payment_intent_id'])): ?>
                                    &mdash;
                                <?php else: ?>
                                    <a href="api-console.php?operation=payment-intent&amp;id=<?= e(urlencode($row['payment_intent_id'])) ?>"
                                       title="Look up the payment intent in the API console">
                                        <?= e($row['payment_intent_id']) ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ((string) $row['amount'] === ''): ?>
                                    —
                                <?php else: ?>
                                    <span class="ccy"><?= e($row['currency'] ?: 'MYR') ?></span>
                                    <?= e(number_format((float) $row['amount'], 2)) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status" data-tone="<?= e(PaymentStatus::tone($row['status'])) ?>">
                                    <?= e(PaymentStatus::label($row['status'])) ?>
                                </span>
                            </td>
                            <td class="id">
                                <?php $t = strtotime((string) $row['created_at']); ?>
                                <?= e($t ? date('d/m H:i', $t) : $row['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="panel-foot">
            <span><b><?= count($transactions) ?></b> transaction<?= count($transactions) === 1 ? '' : 's' ?></span>
            <span class="sep">·</span>
            <span><b class="money">RM <?= e(number_format($collected, 2)) ?></b> collected</span>

            <span class="stat-breakdown">
                <?php foreach ($byStatus as $label => $count): ?>
                    <span class="status" data-tone="<?= e(PaymentStatus::tone(array_search($label, PaymentStatus::LABELS, true))) ?>">
                        <?= e($count) ?> <?= e(strtolower($label)) ?>
                    </span>
                <?php endforeach; ?>
            </span>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head">
        <span>Log</span>
        <span class="meta"><?= e($db->driver()) ?> · storage/logs/demo.log</span>
    </div>
    <div class="panel-body tight">
        <?php if ($recentLog === []): ?>
            <div class="empty" style="padding:1rem 0">
                Empty. Callbacks are server-to-server, so this is where they surface.
            </div>
        <?php else: ?>
            <div class="log-block">
                <pre class="code"><?php foreach ($recentLog as $line) { echo e($line), "\n"; } ?></pre>

                <form class="log-clear" method="POST" onsubmit="return confirm('Clear the log?');">
                    <button class="btn btn-small" type="submit" name="clear_log" value="1">Clear</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php layout_foot($config); ?>
